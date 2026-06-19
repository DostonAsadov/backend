<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AdminLoginRequest;
use App\Services\Auth\AdminAuthService;
use Illuminate\Http\JsonResponse;


class AdminAuthController extends Controller
{
    //
    public function __construct(private AdminAuthService $adminAuthService) {}

    public function login(AdminLoginRequest $request): JsonResponse
    {
        $result = $this->adminAuthService->login($request->validated());

        if (!$result) {
            return response()->json([
                'message' => 'Неверный почта или пароль'
            ], 401);
        }

        return response()->json($result);
    }

    public function logout(): JsonResponse
    {
        $this->adminAuthService->logout();
        return response()->json([
            'message' => 'Выход выполнен'
        ]);
    }

    public function refresh(): JsonResponse
    {
        $result = $this->adminAuthService->refresh();
        return response()->json($result);
    }

    public function me(): JsonResponse
    {
        $result = $this->adminAuthService->me();
        return response()->json($result);
    }
}
