<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => User::query()
                ->orderBy('name')
                ->get()
                ->map(fn (User $user) => $user->frontendProfile())
                ->values(),
        ]);
    }

    public function store(Request $request, AuditLogger $audit): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:180', 'unique:users,email'],
            'role' => ['required', Rule::in(['administrator', 'consultant', 'auditor'])],
            'password' => ['required', 'string', 'min:10', 'max:100'],
        ]);

        $user = User::create([
            ...$validated,
            'email' => mb_strtolower($validated['email']),
            'is_active' => true,
        ]);

        $audit->record($request, 'user.created', 'user', $user->id, [
            'email' => $user->email,
            'role' => $user->role,
        ]);

        return response()->json([
            'message' => 'Usuario creado correctamente.',
            'data' => $user->frontendProfile(),
        ], 201);
    }
}
