<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request, AuditLogger $audit): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        $authenticated = Auth::attempt([
            'email' => mb_strtolower($validated['email']),
            'password' => $validated['password'],
            'is_active' => true,
        ], (bool) ($validated['remember'] ?? false));

        if (! $authenticated) {
            $audit->record($request, 'auth.login_failed', metadata: [
                'email' => mb_strtolower($validated['email']),
            ]);

            throw ValidationException::withMessages([
                'email' => ['El correo, la contraseña o el estado de la cuenta no son válidos.'],
            ]);
        }

        $request->session()->regenerate();
        $audit->record($request, 'auth.login_succeeded', 'user', $request->user()->id);

        return response()->json([
            'message' => 'Sesión iniciada correctamente.',
            'data' => $request->user()->frontendProfile(),
            'csrfToken' => csrf_token(),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['data' => $request->user()->frontendProfile()]);
    }

    public function logout(Request $request, AuditLogger $audit): JsonResponse
    {
        $audit->record($request, 'auth.logout', 'user', $request->user()->id);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
            'csrfToken' => csrf_token(),
        ]);
    }
}
