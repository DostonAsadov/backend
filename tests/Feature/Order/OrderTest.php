<?php

namespace Tests\Feature\Order;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    private function customer(string $phone = '+998901112233'): Customer
    {
        return Customer::create(['name' => 'Buyer', 'phone' => $phone, 'password' => 'secret123']);
    }

    private function checkoutPayload(array $items, string $payment = 'cash'): array
    {
        return [
            'items'          => $items,
            'delivery'       => [
                'name'    => 'Buyer',
                'phone'   => '+998901112233',
                'method'  => 'courier',
                'address' => 'Ташкент, Чиланзар 1',
                'comment' => 'Позвонить заранее',
            ],
            'payment_method' => $payment,
        ];
    }

    private function placeOrder(Customer $customer, array $items, string $payment = 'cash'): int
    {
        return $this->actingAs($customer, 'customer')
            ->postJson('/api/v1/cart/checkout', $this->checkoutPayload($items, $payment))
            ->assertCreated()
            ->json('data.id');
    }

    public function test_checkout_requires_customer_auth(): void
    {
        $this->postJson('/api/v1/cart/checkout', [])->assertUnauthorized();

        $this->actingAs(User::factory()->admin()->create(), 'api')
            ->postJson('/api/v1/cart/checkout', [])
            ->assertUnauthorized();
    }

    public function test_checkout_uses_db_prices_and_decrements_stock(): void
    {
        $motor = Product::factory()->create(['price' => 1_500_000.50, 'stock' => 5, 'name_uz' => 'Motor']);
        $brake = Product::factory()->create(['price' => 120_000, 'stock' => 3]);

        $this->actingAs($this->customer(), 'customer')
            ->postJson('/api/v1/cart/checkout', $this->checkoutPayload([
                ['product_id' => $motor->id, 'qty' => 2, 'price' => 1], // цена от клиента игнорируется
                ['product_id' => $brake->id, 'qty' => 1],
            ]))
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.payment_status', 'pending')
            ->assertJsonPath('data.total', '3120001.00')
            ->assertJsonPath('data.items.0.name', 'Motor')
            ->assertJsonPath('data.items.0.price', '1500000.50')
            ->assertJsonPath('data.delivery.address', 'Ташкент, Чиланзар 1');

        $this->assertSame(3, $motor->fresh()->stock);
        $this->assertSame(2, $brake->fresh()->stock);
    }

    public function test_price_change_does_not_affect_existing_order(): void
    {
        $product = Product::factory()->create(['price' => 100_000]);
        $customer = $this->customer();
        $id = $this->placeOrder($customer, [['product_id' => $product->id, 'qty' => 1]]);

        $product->update(['price' => 999_000]);

        $this->getJson("/api/v1/user/orders/{$id}")
            ->assertJsonPath('data.total', '100000.00')
            ->assertJsonPath('data.items.0.price', '100000.00');
    }

    public function test_checkout_rejects_insufficient_stock_and_rolls_back(): void
    {
        $ok = Product::factory()->create(['stock' => 5]);
        $low = Product::factory()->create(['stock' => 1]);

        $this->actingAs($this->customer(), 'customer')
            ->postJson('/api/v1/cart/checkout', $this->checkoutPayload([
                ['product_id' => $ok->id, 'qty' => 2],
                ['product_id' => $low->id, 'qty' => 2],
            ]))
            ->assertStatus(422);

        $this->assertSame(5, $ok->fresh()->stock); // списание первой позиции откатилось
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_rejects_inactive_product(): void
    {
        $hidden = Product::factory()->inactive()->create();

        $this->actingAs($this->customer(), 'customer')
            ->postJson('/api/v1/cart/checkout', $this->checkoutPayload([['product_id' => $hidden->id, 'qty' => 1]]))
            ->assertStatus(422);
    }

    public function test_checkout_validation(): void
    {
        $product = Product::factory()->create();
        $payload = $this->checkoutPayload([
            ['product_id' => $product->id, 'qty' => 1],
            ['product_id' => $product->id, 'qty' => 0],
        ], 'balance');
        $payload['delivery']['address'] = null;
        $payload['delivery']['extra'] = 'x';

        $this->actingAs($this->customer(), 'customer')
            ->postJson('/api/v1/cart/checkout', $payload)
            ->assertJsonValidationErrors([
                'items.0.product_id', 'items.1.qty', 'delivery', 'delivery.address', 'payment_method',
            ]);

        // самовывоз — адрес не нужен
        $payload = $this->checkoutPayload([['product_id' => $product->id, 'qty' => 1]]);
        $payload['delivery']['method'] = 'pickup';
        unset($payload['delivery']['address']);

        $this->postJson('/api/v1/cart/checkout', $payload)->assertCreated();
    }

    public function test_customer_sees_only_own_orders(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $alice = $this->customer('+998900000001');
        $bob = $this->customer('+998900000002');

        $aliceOrder = $this->placeOrder($alice, [['product_id' => $product->id, 'qty' => 1]]);
        $this->placeOrder($alice, [['product_id' => $product->id, 'qty' => 1]]);
        $bobOrder = $this->placeOrder($bob, [['product_id' => $product->id, 'qty' => 1]]);

        $this->actingAs($alice, 'customer')
            ->getJson('/api/v1/user/orders')
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $aliceOrder + 1); // новые сверху

        $this->getJson("/api/v1/user/orders/{$bobOrder}")->assertNotFound();
    }

    public function test_admin_status_flow_for_cash_order(): void
    {
        $product = Product::factory()->create(['stock' => 5]);
        $id = $this->placeOrder($this->customer(), [['product_id' => $product->id, 'qty' => 1]]);

        $this->actingAs(User::factory()->create(), 'api');

        $this->getJson('/api/v1/admin/orders?status=pending')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.customer.phone', '+998901112233');

        // paid ставит только вебхук
        $this->putJson("/api/v1/admin/orders/{$id}/status", ['status' => 'paid'])->assertStatus(422);
        // нельзя перескочить через статусы
        $this->putJson("/api/v1/admin/orders/{$id}/status", ['status' => 'completed'])->assertStatus(422);

        foreach (['processing', 'shipped', 'completed'] as $status) {
            $this->putJson("/api/v1/admin/orders/{$id}/status", ['status' => $status])
                ->assertOk()
                ->assertJsonPath('data.status', $status);
        }

        // наличные: при завершении заказ считается оплаченным
        $this->assertSame('paid', Order::find($id)->payment_status->value);
        $this->putJson("/api/v1/admin/orders/{$id}/status", ['status' => 'cancelled'])->assertStatus(422);
    }

    public function test_online_order_cannot_be_processed_before_payment(): void
    {
        $product = Product::factory()->create();
        $id = $this->placeOrder($this->customer(), [['product_id' => $product->id, 'qty' => 1]], 'click');

        $this->actingAs(User::factory()->create(), 'api')
            ->putJson("/api/v1/admin/orders/{$id}/status", ['status' => 'processing'])
            ->assertStatus(422);
    }

    public function test_cancel_restores_stock(): void
    {
        $product = Product::factory()->create(['stock' => 5]);
        $id = $this->placeOrder($this->customer(), [['product_id' => $product->id, 'qty' => 3]]);
        $this->assertSame(2, $product->fresh()->stock);

        $this->actingAs(User::factory()->create(), 'api')
            ->putJson("/api/v1/admin/orders/{$id}/status", ['status' => 'cancelled'])
            ->assertOk();

        $this->assertSame(5, $product->fresh()->stock);
    }

    public function test_deleted_product_keeps_order_item_snapshot(): void
    {
        $product = Product::factory()->create(['sku' => 'MT-1', 'name_ru' => 'Мотор']);
        $id = $this->placeOrder($this->customer(), [['product_id' => $product->id, 'qty' => 1]]);

        $this->actingAs(User::factory()->create(), 'api')
            ->deleteJson("/api/v1/admin/products/{$product->id}")
            ->assertOk();

        $this->getJson("/api/v1/admin/orders/{$id}")
            ->assertOk()
            ->assertJsonPath('data.items.0.product_id', null)
            ->assertJsonPath('data.items.0.sku', 'MT-1')
            ->assertJsonPath('data.items.0.name_ru', 'Мотор');
    }
}
