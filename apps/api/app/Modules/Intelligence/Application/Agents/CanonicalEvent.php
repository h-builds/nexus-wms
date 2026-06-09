<?php

declare(strict_types=1);

namespace App\Modules\Intelligence\Application\Agents;

/**
 * Read-only representation of a dispatched domain event
 * consumed by decision agents. Mirrors the outbox payload structure.
 */
final readonly class CanonicalEvent
{
    public function __construct(
        public string $eventId,
        public string $eventType,
        public int $eventVersion,
        public string $occurredAt,
        public ?string $actorId,
        public string $correlationId,
        public string $causationId,
        public array $payload,
    ) {}
}
