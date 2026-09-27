<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AdminProductIndexRequest extends FormRequest
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
            'q'           => ['sometimes', 'string', 'max:100'],
            'category_id' => ['sometimes', 'integer'],
            'is_active'   => ['sometimes', 'boolean'],
            'per_page'    => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page'        => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
