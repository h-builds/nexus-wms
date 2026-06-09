<?php

declare(strict_types=1);

namespace App\Modules\Intelligence\Application\Actions;

use App\Modules\Events\Application\Services\BroadcastableOutboxEvent;
use App\Modules\Intelligence\Application\Agents\AgentExecutor;
use App\Modules\Intelligence\Application\Agents\CanonicalEvent;
use Illuminate\Support\Facades\Log;

final readonly class EvaluateOutboxEventAction
{
    public function __construct(
        private AgentExecutor $agentExecutor,
    ) {
    }

    public function execute(BroadcastableOutboxEvent $broadcastableEvent): void
    {
        $canonicalEvent = $this->mapOutboxToCanonicalEvent($broadcastableEvent);

        try {
            $this->agentExecutor->evaluate($canonicalEvent);
        } catch (\Throwable $agentException) {
            $this->logEvaluationFailure($canonicalEvent, $agentException);
        }
    }

    private function mapOutboxToCanonicalEvent(BroadcastableOutboxEvent $broadcastableEvent): CanonicalEvent
    {
        $payload = $broadcastableEvent->broadcastWith();

        return new CanonicalEvent(
            eventId: $payload['eventId'],
            eventType: $payload['eventType'],
            eventVersion: (int) $payload['eventVersion'],
            occurredAt: (string) $payload['occurredAt'],
            actorId: $payload['actorId'],
            correlationId: $payload['correlationId'] ?? '',
            causationId: $payload['causationId'] ?? '',
            payload: is_array($payload['payload']) ? $payload['payload'] : [],
        );
    }

    private function logEvaluationFailure(CanonicalEvent $canonicalEvent, \Throwable $agentException): void
    {
        try {
            report($agentException);

            Log::error('Intelligence Layer execution failed', [
                'eventId' => $canonicalEvent->eventId,
                'errorMessage' => $agentException->getMessage(),
                'trace' => $agentException->getTraceAsString(),
            ]);
        } catch (\Throwable $loggingException) {
            // Last-resort fallback: never break the event pipeline, but never lose the error either
            error_log(sprintf(
                '[Intelligence] Agent evaluation failed for event %s: %s (logging also failed: %s)',
                $canonicalEvent->eventId,
                $agentException->getMessage(),
                $loggingException->getMessage(),
            ));
        }
    }
}
