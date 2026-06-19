<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\Auth\CustomerAuthService;
use App\Http\Requests\Auth\CustomerRegisterRequest;
use App\Http\Requests\Auth\CustomerLoginRequest;
use Illuminate\Http\JsonResponse;

class CustomerAuthController extends Controller
{
    public function __construct(private CustomerAuthService $authService) {}

    public function register(CustomerRegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());
        return response()->json($result, 201);
    }

    public function login(CustomerLoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        if (!$result) {
            return response()->json([
                'message' => 'Неверный телефон или пароль'
            ], 401);
        }

        return response()->json($result);
    }

    public function logout(): JsonResponse
    {
        $this->authService->logout();
        return response()->json(['message' => 'Выход выполнен']);
    }

    public function refresh(): JsonResponse
    {
        $result = $this->authService->refresh();
        return response()->json($result);
    }

    public function me(): JsonResponse
    {
        $result = $this->authService->me();
        return response()->json($result);
    }
}
