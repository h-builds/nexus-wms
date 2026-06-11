<?php

declare(strict_types=1);

namespace App\Modules\Events\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use App\Modules\Events\Domain\Repositories\EventOutboxRepository;
use App\Modules\Events\Infrastructure\Persistence\Repositories\EloquentEventOutboxRepository;

final class EventServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(EventOutboxRepository::class, EloquentEventOutboxRepository::class);
    }
}
