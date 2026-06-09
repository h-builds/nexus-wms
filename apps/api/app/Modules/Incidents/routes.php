<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Modules\Incidents\Infrastructure\Http\Controllers\IncidentController;

Route::prefix('api/incidents')
    ->middleware('api')
    ->group(function () {
        Route::get('/', [IncidentController::class, 'listIncidents']);
        Route::get('/{id}', [IncidentController::class, 'viewIncident']);
        Route::post('/', [IncidentController::class, 'reportIncident'])->middleware('idempotent');
        Route::patch('/{id}', [IncidentController::class, 'updateIncidentMetadata']);
        Route::patch('/{id}/status', [IncidentController::class, 'transitionIncidentStatus']);
    });
