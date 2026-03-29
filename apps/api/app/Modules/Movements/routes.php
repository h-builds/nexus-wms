<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Movements\Infrastructure\Http\Controllers\MovementController;

Route::prefix('api/movements')
    ->middleware('api')
    ->group(function () {
        Route::get('/', [MovementController::class, 'index']);
        Route::get('/{id}', [MovementController::class, 'show']);
        Route::post('/', [MovementController::class, 'store']);
    });
