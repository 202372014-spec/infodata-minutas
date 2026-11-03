<?php

use App\Http\Controllers\MinuteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MinuteController::class, 'index'])->name('minutes.index');
Route::get('/api/minutas', [MinuteController::class, 'apiIndex'])->name('minutes.api.index');
Route::get('/api/minutas/{minute:folio}', [MinuteController::class, 'show'])->name('minutes.api.show');
Route::post('/api/minutas', [MinuteController::class, 'store'])->name('minutes.api.store');
