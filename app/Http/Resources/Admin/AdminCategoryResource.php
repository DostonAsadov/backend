<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Категория для админки — обе локали сразу, для формы редактирования.
 */
class AdminCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'parent_id'  => $this->parent_id,
            'slug'       => $this->slug,
            'name_ru'    => $this->name_ru,
            'name_uz'    => $this->name_uz,
            'children'   => AdminCategoryResource::collection($this->whenLoaded('children')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
