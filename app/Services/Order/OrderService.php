<?php

namespace App\Services\Order;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class OrderService
{
    private const DEFAULT_PER_PAGE = 20;

    /**
     * Смены статуса, доступные менеджеру в админке.
     * `paid` здесь нет: его ставит только вебхук Click/Payme.
     */
    private const TRANSITIONS = [
        'pending'    => ['processing', 'cancelled'],
        'paid'       => ['processing', 'cancelled'],
        'processing' => ['shipped', 'cancelled'],
        'shipped'    => ['completed'],
        'completed'  => [],
        'cancelled'  => [],
    ];

    /**
     * Оформление заказа из корзины.
     * Цены берутся из БД (не от клиента), остатки списываются сразу — всё в одной транзакции.
     *
     * @param  array{items: array<array{product_id:int, qty:int}>, delivery: array, payment_method: string}  $data
     */
    public function checkout(Customer $customer, array $data): Order
    {
        return DB::transaction(function () use ($customer, $data) {
            $qtyByProduct = collect($data['items'])->pluck('qty', 'product_id');

            // блокируем строки товаров, чтобы два заказа не продали один остаток
            $products = Product::whereIn('id', $qtyByProduct->keys())
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $totalCents = 0;
            $items = [];

            foreach ($qtyByProduct as $productId => $qty) {
                $product = $products->get($productId);

                if (!$product || !$product->is_active) {
                    throw new BusinessRuleException("Товар #{$productId} недоступен для заказа.");
                }

                if ($product->stock < $qty) {
                    throw new BusinessRuleException("Недостаточно товара «{$product->name_ru}» на складе (осталось {$product->stock}).");
                }

                $product->decrement('stock', $qty);

                // считаем в тийинах, чтобы не терять точность на float
                $totalCents += (int) round($product->price * 100) * $qty;

                $items[] = [
                    'product_id'     => $product->id,
                    'sku'            => $product->sku,
                    'name_ru'        => $product->name_ru,
                    'name_uz'        => $product->name_uz,
                    'qty'            => $qty,
                    'price_snapshot' => $product->price,
                ];
            }

            $order = $customer->orders()->create([
                'status'           => OrderStatus::Pending,
                'total'            => $totalCents / 100,
                'delivery_address' => $data['delivery'],
                'payment_method'   => PaymentMethod::from($data['payment_method']),
                'payment_status'   => PaymentStatus::Pending,
            ]);

            $order->items()->createMany($items);

            return $order->load('items');
        });
    }

    public function customerOrders(Customer $customer, ?int $perPage = null): LengthAwarePaginator
    {
        return $customer->orders()
            ->with('items')
            ->latest('id')
            ->paginate($perPage ?? self::DEFAULT_PER_PAGE);
    }

    /**
     * Заказ покупателя; чужой заказ — 404.
     */
    public function customerOrder(Customer $customer, int $orderId): Order
    {
        return $customer->orders()->with('items')->findOrFail($orderId);
    }

    /**
     * Список для админки. Фильтры: status, payment_status, customer_id.
     */
    public function adminList(array $filters): LengthAwarePaginator
    {
        $query = Order::with(['customer', 'items'])->latest('id');

        foreach (['status', 'payment_status', 'customer_id'] as $field) {
            if (!empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        return $query->paginate($filters['per_page'] ?? self::DEFAULT_PER_PAGE);
    }

    /**
     * Смена статуса менеджером. При отмене остатки возвращаются на склад,
     * при завершении заказа с оплатой наличными он считается оплаченным.
     */
    public function changeStatus(Order $order, OrderStatus $status): Order
    {
        return DB::transaction(function () use ($order, $status) {
            $order = Order::lockForUpdate()->findOrFail($order->id);
            $from = $order->status;

            if (!in_array($status->value, self::TRANSITIONS[$from->value], true)) {
                throw new BusinessRuleException("Нельзя перевести заказ из статуса «{$from->value}» в «{$status->value}».");
            }

            // онлайн-заказ нельзя собирать до подтверждения оплаты
            if ($from === OrderStatus::Pending
                && $status === OrderStatus::Processing
                && in_array($order->payment_method, [PaymentMethod::Click, PaymentMethod::Payme], true)) {
                throw new BusinessRuleException('Заказ ещё не оплачен.');
            }

            if ($status === OrderStatus::Cancelled) {
                $this->restoreStock($order);
            }

            $order->status = $status;

            if ($status === OrderStatus::Completed && $order->payment_method === PaymentMethod::Cash) {
                $order->payment_status = PaymentStatus::Paid;
            }

            $order->save();

            return $order->load(['customer', 'items']);
        });
    }

    private function restoreStock(Order $order): void
    {
        foreach ($order->items as $item) {
            if ($item->product_id) {
                Product::whereKey($item->product_id)->increment('stock', $item->qty);
            }
        }
    }
}
