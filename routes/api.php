<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\CustomerAuthController;
use App\Http\Controllers\Api\V1\Auth\AdminAuthController;
use App\Http\Controllers\Api\V1\Catalog\CategoryController;
use App\Http\Controllers\Api\V1\Catalog\ProductController;
use App\Http\Controllers\Api\V1\Customer\OrderController;
use App\Http\Controllers\Api\V1\Admin\AdminUserController;
use App\Http\Controllers\Api\V1\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\V1\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\V1\Admin\ProductController as AdminProductController;

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

    // Каталог (публичный)
    Route::get('categories',        [CategoryController::class, 'index']);
    Route::get('products',          [ProductController::class, 'index']);
    Route::get('products/search',   [ProductController::class, 'search']);
    Route::get('products/{slug}',   [ProductController::class, 'show']);

    // Заказы покупателя (корзина хранится на фронтенде)
    Route::middleware('auth:customer')->group(function () {
        Route::post('cart/checkout',      [OrderController::class, 'checkout']);
        Route::get('user/orders',         [OrderController::class, 'index']);
        Route::get('user/orders/{order}', [OrderController::class, 'show'])->whereNumber('order');
    });

    // Админка — любой активный сотрудник (admin, manager)
    Route::prefix('admin')->middleware(['auth:api', 'role:admin,manager'])->group(function () {
        Route::apiResource('categories', AdminCategoryController::class);
        Route::apiResource('products',   AdminProductController::class);
        Route::post('products/{product}/images',   [AdminProductController::class, 'uploadImages']);
        Route::delete('products/{product}/images', [AdminProductController::class, 'deleteImage']);

        Route::get('orders',                  [AdminOrderController::class, 'index']);
        Route::get('orders/{order}',          [AdminOrderController::class, 'show']);
        Route::put('orders/{order}/status',   [AdminOrderController::class, 'updateStatus']);

        // Управление сотрудниками — только admin
        Route::middleware('role:admin')->group(function () {
            Route::apiResource('users', AdminUserController::class)->except('show');
        });
    });
});
