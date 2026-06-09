<?php

declare(strict_types=1);

namespace App\Modules\Incidents\Application\DTOs;

use App\Modules\Incidents\Domain\Enums\IncidentSeverity;
use App\Modules\Incidents\Domain\Enums\IncidentType;

final readonly class ReportIncidentDTO
{
    public function __construct(
        public string $productId,
        public ?string $locationId,
        public IncidentType $type,
        public IncidentSeverity $severity,
        public string $description,
        public ?int $quantityAffected,
        public string $reportedBy,
        public string $correlationId,
        public ?string $idempotencyKey = null,
    ) {}
}
