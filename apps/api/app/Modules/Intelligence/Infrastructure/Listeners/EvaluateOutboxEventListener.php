<?php

declare(strict_types=1);

namespace App\Modules\Intelligence\Infrastructure\Listeners;

use App\Modules\Events\Application\Services\BroadcastableOutboxEvent;
use App\Modules\Intelligence\Application\Actions\EvaluateOutboxEventAction;
use Psr\Log\LoggerInterface;

final class EvaluateOutboxEventListener
{
    public function __construct(
        private readonly EvaluateOutboxEventAction $action,
        private readonly LoggerInterface $logger
    ) {}

    public function handle(BroadcastableOutboxEvent $event): void
    {
        try {
            $this->action->execute($event);
        } catch (\Throwable $exception) {
            $this->logger->error('Failed to evaluate outbox event in Intelligence agent.', [
                'event_id' => $event->id ?? null,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            throw $exception;
        }
    }
}
