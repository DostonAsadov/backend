<?php

namespace App\Http\Requests\Catalog;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProductIndexRequest extends FormRequest
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
            'category'  => ['sometimes', 'string', 'exists:categories,slug'],
            'min_price' => ['sometimes', 'numeric', 'min:0'],
            'max_price' => ['sometimes', 'numeric', 'min:0', 'gte:min_price'],
            'sort'      => ['sometimes', 'string', 'in:newest,price_asc,price_desc'],
            'per_page'  => ['sometimes', 'integer', 'min:1', 'max:50'],
            'page'      => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
