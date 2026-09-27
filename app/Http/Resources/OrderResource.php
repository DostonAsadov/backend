<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\ResolvesLocale;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Заказ для покупателя — названия позиций на языке из Accept-Language.
 */
class OrderResource extends JsonResource
{
    use ResolvesLocale;

    public function toArray(Request $request): array
    {
        $locale = $this->locale($request);

        return [
            'id'             => $this->id,
            'status'         => $this->status,
            'total'          => $this->total,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'delivery'       => $this->delivery_address,
            'items'          => $this->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'sku'        => $item->sku,
                'name'       => $item->{"name_{$locale}"},
                'qty'        => $item->qty,
                'price'      => $item->price_snapshot,
            ]),
            'created_at'     => $this->created_at,
        ];
    }
}
