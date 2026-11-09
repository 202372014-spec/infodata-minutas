<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MinuteController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MinuteController::class, 'index'])->name('minutes.index');
Route::post('/api/acceso', [AuthController::class, 'login'])->middleware('guest')->name('auth.login');

Route::middleware('auth')->group(function (): void {
    Route::get('/api/sesion', [AuthController::class, 'me'])->name('auth.me');
    Route::post('/api/salir', [AuthController::class, 'logout'])->name('auth.logout');

    Route::get('/api/minutas', [MinuteController::class, 'apiIndex'])->name('minutes.api.index');
    Route::get('/api/minutas/{minute:folio}', [MinuteController::class, 'show'])->name('minutes.api.show');
    Route::post('/api/minutas', [MinuteController::class, 'store'])
        ->middleware('role:administrator,consultant')
        ->name('minutes.api.store');

    Route::get('/api/usuarios', [UserController::class, 'index'])
        ->middleware('role:administrator')
        ->name('users.api.index');
    Route::post('/api/usuarios', [UserController::class, 'store'])
        ->middleware('role:administrator')
        ->name('users.api.store');

    Route::get('/api/auditoria', [AuditLogController::class, 'index'])
        ->middleware('role:administrator,auditor')
        ->name('audit.api.index');
});
