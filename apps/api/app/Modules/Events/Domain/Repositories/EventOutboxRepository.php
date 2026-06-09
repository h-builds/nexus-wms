<?php

declare(strict_types=1);

namespace App\Modules\Events\Domain\Repositories;

use App\Modules\Events\Domain\DTOs\DomainEventPayload;

interface EventOutboxRepository
{
    public function save(
        string $eventId,
        string $eventType,
        DomainEventPayload $payload,
        string $actorId,
        string $correlationId,
        int $eventVersion
    ): void;
}
