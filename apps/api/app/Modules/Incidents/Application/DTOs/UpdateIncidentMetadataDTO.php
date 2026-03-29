<?php

declare(strict_types=1);

namespace App\Modules\Incidents\Application\DTOs;

final readonly class UpdateIncidentMetadataDTO
{
    public function __construct(
        public string $incidentId,
        public ?string $notes,
        public ?string $assignedTo,
        public string $performedBy,
    ) {}
}
