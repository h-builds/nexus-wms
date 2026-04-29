<?php

declare(strict_types=1);

namespace App\Modules\Intelligence\Infrastructure\Providers;

use App\Modules\Events\Application\Services\BroadcastableOutboxEvent;
use App\Modules\Intelligence\Application\Agents\AgentExecutor;
use App\Modules\Intelligence\Application\Agents\InventoryAnomalyAgent;
use App\Modules\Intelligence\Application\Queries\DecisionTraceQueryService;
use App\Modules\Intelligence\Domain\Repositories\DecisionTraceRepository;
use App\Modules\Intelligence\Infrastructure\Listeners\EvaluateOutboxEventListener;
use App\Modules\Intelligence\Infrastructure\Persistence\Repositories\EloquentDecisionTraceRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

final class IntelligenceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DecisionTraceRepository::class, EloquentDecisionTraceRepository::class);
        $this->app->bind(DecisionTraceQueryService::class, EloquentDecisionTraceRepository::class);

        $this->app->singleton(AgentExecutor::class, function (Application $app) {
            return new AgentExecutor(
                agents: [
                    new InventoryAnomalyAgent(),
                ],
                repository: $app->make(DecisionTraceRepository::class),
            );
        });
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes.php');

        $eventDispatcher = $this->app->make(Dispatcher::class);

        $eventDispatcher->listen(
            BroadcastableOutboxEvent::class, 
            EvaluateOutboxEventListener::class
        );
    }
}

