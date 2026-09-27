<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Доступ к роуту только для активных сотрудников с указанными ролями.
 * Использование: ->middleware('role:admin') или ->middleware('role:admin,manager')
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = auth()->guard('api')->user();

        if (!$user || !$user->is_active || !in_array($user->role->value, $roles, true)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        return $next($request);
    }
}
