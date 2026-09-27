<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'status'         => $this->status,
            'total'          => $this->total,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'delivery'       => $this->delivery_address,
            'customer'       => $this->whenLoaded('customer', fn () => [
                'id'    => $this->customer->id,
                'name'  => $this->customer->name,
                'phone' => $this->customer->phone,
            ]),
            'items'          => $this->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'sku'        => $item->sku,
                'name_ru'    => $item->name_ru,
                'name_uz'    => $item->name_uz,
                'qty'        => $item->qty,
                'price'      => $item->price_snapshot,
            ]),
            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,
        ];
    }
}
