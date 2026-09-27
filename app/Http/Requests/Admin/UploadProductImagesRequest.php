<?php

namespace App\Http\Requests\Admin;

use App\Services\Catalog\ProductService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UploadProductImagesRequest extends FormRequest
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
            'images'   => ['required', 'array', 'min:1', 'max:' . ProductService::MAX_IMAGES],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'], // до 4 МБ
        ];
    }
}
