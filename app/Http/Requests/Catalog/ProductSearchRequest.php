<?php

namespace App\Http\Requests\Catalog;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProductSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'q'        => ['required', 'string', 'min:2', 'max:100'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'page'     => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
