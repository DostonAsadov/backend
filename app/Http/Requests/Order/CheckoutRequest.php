<?php

namespace App\Http\Requests\Order;

use App\Enums\DeliveryMethod;
use App\Enums\PaymentMethod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
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
            'items'              => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.qty'        => ['required', 'integer', 'min:1', 'max:100'],

            'delivery'           => ['required', 'array:name,phone,method,address,comment'],
            'delivery.name'      => ['required', 'string', 'max:255'],
            'delivery.phone'     => ['required', 'string', 'max:20'],
            'delivery.method'    => ['required', Rule::enum(DeliveryMethod::class)],
            'delivery.address'   => ['required_if:delivery.method,courier', 'nullable', 'string', 'max:500'],
            'delivery.comment'   => ['nullable', 'string', 'max:1000'],

            // оплата с баланса появится вместе с историей операций по балансу
            'payment_method'     => ['required', Rule::enum(PaymentMethod::class)->except(PaymentMethod::Balance)],
        ];
    }
}
