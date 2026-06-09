<?php

declare(strict_types=1);

namespace App\Modules\Incidents\Application\DTOs;

use App\Modules\Events\Domain\DTOs\DomainEventPayload;

final readonly class IncidentReportedEventPayload implements DomainEventPayload
{
    public function __construct(
        public string $incidentId,
        public string $productId,
        public ?string $locationId,
        public string $type,
        public string $description,
    ) {}

    public function toArray(): array
    {
        return [
            'incidentId' => $this->incidentId,
            'productId' => $this->productId,
            'locationId' => $this->locationId,
            'type' => $this->type,
            'description' => $this->description,
        ];
    }
}
