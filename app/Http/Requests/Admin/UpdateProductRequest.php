<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
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
        $product = $this->route('product');

        return [
            'category_id'    => ['sometimes', 'integer', 'exists:categories,id'],
            'sku'            => ['sometimes', 'string', 'max:64', Rule::unique('products', 'sku')->ignore($product)],
            'name_ru'        => ['sometimes', 'string', 'max:255'],
            'name_uz'        => ['sometimes', 'string', 'max:255'],
            'description_ru' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'description_uz' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'slug'           => ['sometimes', 'string', 'alpha_dash', 'max:255', Rule::unique('products', 'slug')->ignore($product)],
            'price'          => ['sometimes', 'numeric', 'min:0', 'max:9999999999'],
            'stock'          => ['sometimes', 'integer', 'min:0'],
            'is_active'      => ['sometimes', 'boolean'],
        ];
    }
}
