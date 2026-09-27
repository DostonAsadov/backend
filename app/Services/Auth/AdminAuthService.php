<?php

namespace App\Services\Auth;


class AdminAuthService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function login(array $credentials): array
    {
        // Логика аутентификации администратора
        // деактивированные сотрудники не могут войти
        if ($token = auth()->guard('api')->attempt([...$credentials, 'is_active' => true])) {
            return ['token' => $token];
        }
        return [];
    }

    public function logout(): void
    {
        // 
        auth()->guard('api')->logout();
    }

    public function refresh(): array
    {
        return ['token' => auth()->guard('api')->refresh()];
    }

    public function me(): array
    {
        return ['user' => auth()->guard('api')->user()];
    }
}
