<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasFactory, HasSlug;

    protected $fillable = [
        'category_id',
        'sku',
        'name_ru',
        'name_uz',
        'description_ru',
        'description_uz',
        'slug',
        'price',
        'stock',
        'images',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price'     => 'decimal:2',
            'stock'     => 'integer',
            'images'    => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // только товары, видимые в публичном каталоге
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
