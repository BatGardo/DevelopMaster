<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Role
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        // якщо мідлвар підвісили глобально або без параметрів — пропускаємо
        if (empty($roles)) {
            return $next($request);
        }

        // нормалізуємо параметри та роль користувача
        $roles = array_filter(array_map(fn($r) => strtolower(trim((string)$r)), $roles));
        $userRole = strtolower(trim((string)$user->role));

        if (! in_array($userRole, $roles, true)) {
            abort(403, 'This action is unauthorized.');
        }

        return $next($request);
    }
}
