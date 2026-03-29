<?php

declare(strict_types=1);

namespace App\Modules\Incidents\Application\DTOs;

final class ReportIncidentDTO
{
    public function __construct(
        public readonly string $productId,
        public readonly ?string $locationId,
        public readonly string $type,
        public readonly string $severity,
        public readonly string $description,
        public readonly ?int $quantityAffected,
        public readonly string $reportedBy,
        public readonly ?string $idempotencyKey = null,
    ) {}
}
