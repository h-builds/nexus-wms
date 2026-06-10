<?php

declare(strict_types=1);

namespace App\Modules\Events\Infrastructure\Persistence\Repositories;

use App\Modules\Events\Domain\DTOs\DomainEventPayload;
use App\Modules\Events\Domain\Repositories\EventOutboxRepository;
use App\Modules\Events\Infrastructure\Persistence\Eloquent\EventOutboxModel;

final class EloquentEventOutboxRepository implements EventOutboxRepository
{
    public function save(
        string $eventId,
        string $eventType,
        DomainEventPayload $payload,
        string $actorId,
        string $correlationId,
        int $eventVersion
    ): void {
        try {
            EventOutboxModel::create([
                'event_id' => $eventId,
                'event_type' => $eventType,
                'event_version' => $eventVersion,
                'occurred_at' => now(),
                'actor_id' => $actorId,
                'correlation_id' => $correlationId,
                'causation_id' => $eventId,
                'payload' => $payload->toArray(),
                'dispatched' => false,
            ]);
        } catch (\Throwable $e) {
            throw new \App\Modules\Events\Infrastructure\Exceptions\EventOutboxPersistenceFailedException(
                'Failed to persist event to outbox',
                0,
                $e
            );
        }
    }
}
