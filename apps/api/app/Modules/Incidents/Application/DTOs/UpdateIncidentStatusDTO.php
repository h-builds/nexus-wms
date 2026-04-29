<?php

declare(strict_types=1);

namespace App\Modules\Incidents\Application\DTOs;

use App\Modules\Incidents\Domain\Enums\IncidentStatus;

final readonly class UpdateIncidentStatusDTO
{
    public function __construct(
        public string $incidentId,
        public IncidentStatus $incidentStatus,
        public string $performedBy,
        public string $correlationId,
    ) {}
}
