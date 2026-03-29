<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Infrastructure\Providers;

use App\Modules\Inventory\Domain\Repositories\StockItemRepository;
use App\Modules\Inventory\Infrastructure\Persistence\Eloquent\EloquentStockItemRepository;
use Illuminate\Support\ServiceProvider;

final class InventoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(StockItemRepository::class, EloquentStockItemRepository::class);
    }

    public function boot(): void
    {
    }
}
