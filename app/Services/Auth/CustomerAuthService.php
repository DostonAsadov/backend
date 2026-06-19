<?php

namespace App\Services\Auth;

use App\Models\Customer;

class CustomerAuthService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //

    }

    public function register(array $data): array
    {
        // Логика регистрации клиента
        $customer = Customer::create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'password' => $data['password'],
            'telegram_id' => $data['telegram_id'] ?? null,
            'telegram_username' => $data['telegram_username'] ?? null,
        ]);
        $token = auth()->guard('customer')->login($customer);
        return ['token' => $token];
    }

    public function login(array $credentials): ?array
    {
        // Логика аутентификации клиента
        if ($token = auth()->guard('customer')->attempt($credentials)) {
            return ['token' => $token];
        }
        return null;
    }

    public function logout(): void
    {
        // Логика выхода клиента
        auth()->guard('customer')->logout();
    }

    public function refresh(): array
    {
        // Логика обновления токена клиента

        return ['token' => auth()->guard('customer')->refresh()];
    }

    public function me(): array
    {
        // Логика получения информации о текущем клиенте

        return ['customer' => auth()->guard('customer')->user()];
    }
}
