<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Товар для админки — обе локали, скрытые товары, пути фото (для удаления) и их URL.
 */
class AdminProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'category_id'    => $this->category_id,
            'sku'            => $this->sku,
            'slug'           => $this->slug,
            'name_ru'        => $this->name_ru,
            'name_uz'        => $this->name_uz,
            'description_ru' => $this->description_ru,
            'description_uz' => $this->description_uz,
            'price'          => $this->price,
            'stock'          => $this->stock,
            'is_active'      => $this->is_active,
            'images'         => array_map(fn (string $path) => [
                'path' => $path,
                'url'  => Storage::disk('public')->url($path),
            ], $this->images ?? []),
            'category'       => new AdminCategoryResource($this->whenLoaded('category')),
            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,
        ];
    }
}
