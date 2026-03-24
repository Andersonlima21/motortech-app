<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Auth\AuthenticationException;

class Authenticate
{
    public function handle(Request $request, Closure $next, ...$guards): Response
    {
        // usa o guard 'api' por padrão
        $guard = $guards[0] ?? 'api';

        if (!auth($guard)->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }

    protected function redirectTo(Request $request): ?string
    {
        // Não tenta redirecionar para "login"
        if ($request->expectsJson()) {
            return null;
        }

        return route('login');
    }
}
