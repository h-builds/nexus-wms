<?php

declare(strict_types=1);

namespace App\Modules\Locations\Application\DTOs;

use App\Modules\Events\Domain\DTOs\DomainEventPayload;

final class LocationCreatedEventPayload implements DomainEventPayload
{
    public function __construct(
        public readonly string $locationId,
        public readonly string $label,
        public readonly string $warehouseCode,
    ) {}

    public function toArray(): array
    {
        return [
            'locationId' => $this->locationId,
            'label' => $this->label,
            'warehouseCode' => $this->warehouseCode,
        ];
    }
}
