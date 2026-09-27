<?php

namespace App\Http\Resources\Concerns;

use Illuminate\Http\Request;

trait ResolvesLocale
{
    /**
     * Язык ответа из заголовка Accept-Language (ru/uz), по умолчанию uz.
     */
    protected function locale(Request $request): string
    {
        return $request->getPreferredLanguage(['uz', 'ru']);
    }
}
