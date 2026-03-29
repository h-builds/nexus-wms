<?php

declare(strict_types=1);

use App\Modules\Inventory\Infrastructure\Http\Controllers\InventoryController;
use Illuminate\Support\Facades\Route;

Route::prefix('inventory')->group(function (): void {
    Route::get('/', [InventoryController::class, 'index']);
    Route::get('/{id}', [InventoryController::class, 'show']);
});
