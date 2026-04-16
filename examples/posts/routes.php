<?php

declare(strict_types=1);

use App\Http\Controllers\Posts;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/posts')
    ->middleware(['force.json', 'auth:sanctum', 'throttle:api'])
    ->group(function (): void {
        Route::get('/', Posts\V1\IndexController::class)->name('v1:index');
        Route::post('/', Posts\V1\StoreController::class)->name('v1:store');
        Route::get('/{post}', Posts\V1\ShowController::class)->name('v1:show');
        Route::put('/{post}', Posts\V1\UpdateController::class)->name('v1:update');
        Route::delete('/{post}', Posts\V1\DestroyController::class)->name('v1:destroy');
    });
