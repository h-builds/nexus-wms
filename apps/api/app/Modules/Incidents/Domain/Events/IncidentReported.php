<?php

declare(strict_types=1);

namespace App\Modules\Incidents\Domain\Events;

use App\Modules\Incidents\Domain\Enums\IncidentType;

final class IncidentReported
{
    public function __construct(
        public readonly string $incidentId,
        public readonly string $productId,
        public readonly ?string $locationId,
        public readonly IncidentType $incidentType,
        public readonly ?string $description,
        public readonly string $occurredAt,
        public readonly ?string $actorId = null,
    ) {
    }
}
