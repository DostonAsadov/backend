<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
        $middleware->redirectGuestsTo(fn() => null);
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Для /api/* всегда JSON (422, 404 и т.д.), даже без заголовка Accept
        $exceptions->shouldRenderJsonWhen(fn(Request $request) => $request->is('api/*'));

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            // Ответ для API-запросов (JSON)
            return response()->json(['message' => 'Пользователь не авторизован.'], 401);
        });
    })->create();
