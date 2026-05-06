<?php

declare(strict_types=1);

namespace App\Modules\Events\Application\Services;

use App\Modules\Events\Application\DTOs\DomainEventPayload;
use App\Modules\Events\Domain\Repositories\EventOutboxRepository;
use App\Modules\Events\Application\Exceptions\EventPublishingFailedException;
use Illuminate\Database\Connection;
use Illuminate\Support\Str;

final class EventPublisher
{
    public function __construct(
        private readonly EventOutboxRepository $outboxRepository,
        private readonly OutboxDispatcher $outboxDispatcher,
        private readonly Connection $db
    ) {
    }

    public function publish(
        string $eventType,
        DomainEventPayload $payload,
        string $actorId,
        string $correlationId,
        int $eventVersion = 1
    ): void {
        $outboxEventId = Str::uuid()->toString();

        try {
            $this->persistToOutbox(
                $outboxEventId,
                $eventType,
                $payload,
                $actorId,
                $correlationId,
                $eventVersion
            );

            $this->scheduleDispatch($outboxEventId, $payload);
        } catch (\Throwable $e) {
            throw new EventPublishingFailedException(
                'Failed to persist and schedule event dispatch.',
                0,
                $e
            );
        }
    }

    private function persistToOutbox(
        string $outboxEventId,
        string $eventType,
        DomainEventPayload $payload,
        string $actorId,
        string $correlationId,
        int $eventVersion
    ): void {
        $this->outboxRepository->save(
            $outboxEventId,
            $eventType,
            $payload,
            $actorId,
            $correlationId,
            $eventVersion
        );
    }

    private function scheduleDispatch(string $outboxEventId, DomainEventPayload $payload): void
    {
        $this->db->afterCommit(function () use ($outboxEventId, $payload) {
            $this->outboxDispatcher->dispatchAndMark($outboxEventId, $payload);
        });
    }
}
