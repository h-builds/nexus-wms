<?php

declare(strict_types=1);

namespace App\Modules\Incidents\Application\DTOs;

use App\Modules\Events\Domain\DTOs\DomainEventPayload;

final readonly class IncidentStatusUpdatedEventPayload implements DomainEventPayload
{
    public function __construct(
        public string $incidentId,
        public string $previousStatus,
        public string $newStatus,
    ) {}

    public function toArray(): array
    {
        return [
            'incidentId' => $this->incidentId,
            'previousStatus' => $this->previousStatus,
            'newStatus' => $this->newStatus,
        ];
    }
}
