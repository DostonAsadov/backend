<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
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
        $category = $this->route('category');

        return [
            'parent_id' => [
                'nullable', 'integer',
                Rule::exists('categories', 'id')->whereNull('parent_id'),
                Rule::notIn([$category->id]),
            ],
            'name_ru'   => ['sometimes', 'string', 'max:255'],
            'name_uz'   => ['sometimes', 'string', 'max:255'],
            'slug'      => ['sometimes', 'string', 'alpha_dash', 'max:255', Rule::unique('categories', 'slug')->ignore($category)],
        ];
    }
}
