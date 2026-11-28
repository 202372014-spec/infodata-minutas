<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if (! $request->routeIs('minutes.index')) {
                return $request->expectsJson()
                    ? response()->json([
                        'message' => 'La cuenta no se encuentra activa.',
                        'csrfToken' => $request->session()->token(),
                    ], 401)
                    : redirect()->route('minutes.index');
            }
        }

        $response = $next($request);
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }
}
