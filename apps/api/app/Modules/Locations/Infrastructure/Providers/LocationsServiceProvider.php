<?php

declare(strict_types=1);

namespace App\Modules\Locations\Infrastructure\Providers;

use App\Modules\Locations\Application\Queries\LocationQueryService;
use App\Modules\Locations\Domain\Repositories\LocationRepository;
use App\Modules\Locations\Infrastructure\Persistence\Eloquent\EloquentLocationQueryService;
use App\Modules\Locations\Infrastructure\Persistence\Eloquent\EloquentLocationRepository;
use Illuminate\Support\ServiceProvider;

final class LocationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(LocationRepository::class, EloquentLocationRepository::class);
        $this->app->bind(LocationQueryService::class, EloquentLocationQueryService::class);
    }

    public function boot(): void
    {
    }
}
