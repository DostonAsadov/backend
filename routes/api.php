<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\CustomerAuthController;
use App\Http\Controllers\Api\V1\Auth\AdminAuthController;

// Публичные роуты — покупатели
Route::prefix('v1')->group(function () {

    // Покупатели
    Route::prefix('auth/customer')->group(function () {
        Route::post('register', [CustomerAuthController::class, 'register']);
        Route::post('login',    [CustomerAuthController::class, 'login']);
        Route::post('refresh',  [CustomerAuthController::class, 'refresh']);

        // Защищенные роуты для покупателей
        Route::middleware('auth:customer')->group(function () {
            Route::post('logout', [CustomerAuthController::class, 'logout']);
            Route::get('me',      [CustomerAuthController::class, 'me']);
        });
    });

    // Админы
    Route::prefix('auth/admin')->group(function () {
        Route::post('login',   [AdminAuthController::class, 'login']);
        Route::post('refresh', [AdminAuthController::class, 'refresh']);

        // Защищенные роуты для админов
        Route::middleware('auth:api')->group(function () {
            Route::post('logout', [AdminAuthController::class, 'logout']);
            Route::get('me',      [AdminAuthController::class, 'me']);
        });
    });
});
