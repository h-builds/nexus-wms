<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Modules\Intelligence\Infrastructure\Http\Controllers\DecisionTraceController;

Route::prefix('api/intelligence/decision-traces')
    ->middleware('api')
    ->group(function () {
        Route::get('/', [DecisionTraceController::class, 'listDecisionTraces']);
        Route::get('/metrics', [DecisionTraceController::class, 'getDecisionTraceMetrics']);
        Route::get('/{id}', [DecisionTraceController::class, 'viewDecisionTrace']);
        Route::patch('/{id}/acknowledge', [DecisionTraceController::class, 'acknowledgeDecisionTrace']);
        Route::patch('/{id}/act-upon', [DecisionTraceController::class, 'actUponDecisionTrace']);
        Route::patch('/{id}/dismiss', [DecisionTraceController::class, 'dismissDecisionTrace']);
    });
