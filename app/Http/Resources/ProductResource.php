<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\ResolvesLocale;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProductResource extends JsonResource
{
    use ResolvesLocale;

    public function toArray(Request $request): array
    {
        $locale = $this->locale($request);

        return [
            'id'          => $this->id,
            'sku'         => $this->sku,
            'slug'        => $this->slug,
            'name'        => $this->{"name_{$locale}"},
            'description' => $this->{"description_{$locale}"},
            'price'       => $this->price,
            'stock'       => $this->stock,
            'in_stock'    => $this->stock > 0,
            'images'      => array_map(
                fn (string $path) => Storage::disk('public')->url($path),
                $this->images ?? []
            ),
            'category'    => new CategoryResource($this->whenLoaded('category')),
        ];
    }
}
