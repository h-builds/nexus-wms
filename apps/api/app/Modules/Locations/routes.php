<?php

declare(strict_types=1);

use App\Modules\Locations\Infrastructure\Http\Controllers\LocationController;
use Illuminate\Support\Facades\Route;

Route::prefix('locations')->group(function (): void {
    Route::get('/', [LocationController::class, 'index']);
    Route::get('/{id}', [LocationController::class, 'show']);
    Route::post('/', [LocationController::class, 'store'])->middleware('idempotent');
});
