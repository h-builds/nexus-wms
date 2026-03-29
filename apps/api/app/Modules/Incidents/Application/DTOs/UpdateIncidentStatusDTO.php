<?php

declare(strict_types=1);

namespace App\Modules\Incidents\Application\DTOs;

final class UpdateIncidentStatusDTO
{
    public function __construct(
        public readonly string $incidentId,
        public readonly string $status,
        public readonly string $performedBy,
    ) {}
}
