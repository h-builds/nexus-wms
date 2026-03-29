<?php

declare(strict_types=1);

namespace App\Modules\Movements\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use App\Modules\Movements\Domain\Repositories\MovementRepository;
use App\Modules\Movements\Infrastructure\Persistence\Repositories\EloquentMovementRepository;

final class MovementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MovementRepository::class, EloquentMovementRepository::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes.php');
    }
}
