<?php

declare(strict_types=1);

use App\Http\Controllers\Auth;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/auth')->middleware(['force.json', 'throttle:api'])->group(function (): void {
    Route::post('/register', Auth\V1\RegisterController::class)->name('v1:register');
    Route::post('/login', Auth\V1\LoginController::class)->name('v1:login');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::delete('/logout', Auth\V1\LogoutController::class)->name('v1:logout');
    });
});
