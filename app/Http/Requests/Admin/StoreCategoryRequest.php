<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
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
            // родителем может быть только корневая категория (2 уровня)
            'parent_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->whereNull('parent_id')],
            'name_ru'   => ['required', 'string', 'max:255'],
            'name_uz'   => ['required', 'string', 'max:255'],
            'slug'      => ['sometimes', 'string', 'alpha_dash', 'max:255', 'unique:categories,slug'],
        ];
    }
}
