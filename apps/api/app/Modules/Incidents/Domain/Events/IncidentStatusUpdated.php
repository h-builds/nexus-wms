<?php

declare(strict_types=1);

namespace App\Modules\Incidents\Domain\Events;

final class IncidentStatusUpdated
{
    public function __construct(
        public readonly string $incidentId,
        public readonly string $previousStatus,
        public readonly string $newStatus,
        public readonly string $occurredAt,
        public readonly ?string $actorId = null,
    ) {
    }
}
