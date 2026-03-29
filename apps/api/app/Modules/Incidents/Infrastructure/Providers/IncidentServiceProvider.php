<?php

declare(strict_types=1);

namespace App\Modules\Incidents\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use App\Modules\Incidents\Domain\Repositories\IncidentRepository;
use App\Modules\Incidents\Infrastructure\Persistence\Repositories\EloquentIncidentRepository;

final class IncidentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(IncidentRepository::class, EloquentIncidentRepository::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes.php');
    }
}
