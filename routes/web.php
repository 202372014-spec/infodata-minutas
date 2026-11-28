<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmailDeliveryController;
use App\Http\Controllers\MinuteController;
use App\Http\Controllers\MinuteDocumentController;
use App\Http\Controllers\MinuteEmailController;
use App\Http\Controllers\SaeImportController;
use App\Http\Controllers\ServiceSessionController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\EnsureActiveAccount;
use Illuminate\Support\Facades\Route;

Route::get('/', [MinuteController::class, 'index'])
    ->middleware(EnsureActiveAccount::class)
    ->name('minutes.index');
Route::post('/api/acceso', [AuthController::class, 'login'])
    ->middleware(['guest', 'throttle:10,1'])
    ->name('auth.login');

Route::middleware(['auth', EnsureActiveAccount::class])->group(function (): void {
    Route::get('/api/sesion', [AuthController::class, 'me'])->name('auth.me');
    Route::post('/api/salir', [AuthController::class, 'logout'])->name('auth.logout');

    Route::get('/api/minutas', [MinuteController::class, 'apiIndex'])->name('minutes.api.index');
    Route::get('/api/minutas/{minute:folio}', [MinuteController::class, 'show'])->name('minutes.api.show');
    Route::get('/api/minutas/{minute:folio}/pdf', [MinuteDocumentController::class, 'download'])
        ->name('minutes.pdf.download');
    Route::post('/api/minutas', [MinuteController::class, 'store'])
        ->middleware('role:administrator,consultant')
        ->name('minutes.api.store');
    Route::post('/api/minutas/{minute:folio}/enviar', [MinuteEmailController::class, 'send'])
        ->middleware('role:administrator,consultant')
        ->name('minutes.email.send');

    Route::get('/api/correos', [EmailDeliveryController::class, 'index'])
        ->name('emails.api.index');

    Route::get('/api/servicios', [ServiceSessionController::class, 'index'])
        ->middleware('role:administrator,consultant')
        ->name('services.api.index');
    Route::post('/api/servicios/importar', [SaeImportController::class, 'store'])
        ->middleware('role:administrator')
        ->name('services.api.import');
    Route::get('/api/servicios/{sourceService}/sesion', [ServiceSessionController::class, 'show'])
        ->middleware('role:administrator,consultant')
        ->name('services.session.show');
    Route::post('/api/servicios/{sourceService}/iniciar', [ServiceSessionController::class, 'start'])
        ->middleware('role:administrator,consultant')
        ->name('services.session.start');
    Route::post('/api/servicios/{sourceService}/finalizar', [ServiceSessionController::class, 'stop'])
        ->middleware('role:administrator,consultant')
        ->name('services.session.stop');

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
