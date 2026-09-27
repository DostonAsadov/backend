<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
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
            'category_id'    => ['required', 'integer', 'exists:categories,id'],
            'sku'            => ['required', 'string', 'max:64', 'unique:products,sku'],
            'name_ru'        => ['required', 'string', 'max:255'],
            'name_uz'        => ['required', 'string', 'max:255'],
            'description_ru' => ['nullable', 'string', 'max:10000'],
            'description_uz' => ['nullable', 'string', 'max:10000'],
            'slug'           => ['sometimes', 'string', 'alpha_dash', 'max:255', 'unique:products,slug'],
            'price'          => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'stock'          => ['required', 'integer', 'min:0'],
            'is_active'      => ['sometimes', 'boolean'],
        ];
    }
}
