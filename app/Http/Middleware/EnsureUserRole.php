<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        abort_unless($user?->is_active, 403, 'La cuenta no se encuentra activa.');
        abort_unless(in_array($user->role, $roles, true), 403, 'No tienes permisos para realizar esta acción.');

        return $next($request);
    }
}
