<?php

namespace App\Http\Requests\Admin;

use App\UserRole;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAdminUserRequest extends FormRequest
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
            'name'      => ['sometimes', 'string', 'max:255'],
            'email'     => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('user'))],
            'password'  => ['sometimes', 'string', 'min:8'],
            'role'      => ['sometimes', Rule::enum(UserRole::class)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
