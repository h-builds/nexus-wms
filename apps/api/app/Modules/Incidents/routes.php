<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Incidents\Infrastructure\Http\Controllers\IncidentController;

Route::prefix('api/incidents')
    ->middleware('api')
    ->group(function () {
        Route::get('/', [IncidentController::class, 'index']);
        Route::get('/{id}', [IncidentController::class, 'show']);
        Route::post('/', [IncidentController::class, 'store'])->middleware('idempotent');
        Route::patch('/{id}', [IncidentController::class, 'update']);
        Route::patch('/{id}/status', [IncidentController::class, 'updateStatus']);
    });
