<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Автоматически генерирует уникальный slug из name_ru при создании,
 * если slug не передан явно. Кириллица транслитерируется: "Мотор 48V" → "motor-48v".
 */
trait HasSlug
{
    protected static function bootHasSlug(): void
    {
        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = static::uniqueSlug($model->name_ru);
            }
        });
    }

    protected static function uniqueSlug(string $source): string
    {
        $base = Str::slug($source) ?: Str::lower(Str::random(8));
        $slug = $base;
        $i = 2;

        while (static::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
