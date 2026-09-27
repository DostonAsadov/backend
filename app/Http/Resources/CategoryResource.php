<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\ResolvesLocale;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    use ResolvesLocale;

    public function toArray(Request $request): array
    {
        $locale = $this->locale($request);

        return [
            'id'        => $this->id,
            'parent_id' => $this->parent_id,
            'slug'      => $this->slug,
            'name'      => $this->{"name_{$locale}"},
            'children'  => CategoryResource::collection($this->whenLoaded('children')),
        ];
    }
}
