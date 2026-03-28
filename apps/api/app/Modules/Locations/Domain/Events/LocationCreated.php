<?php

declare(strict_types=1);

namespace App\Modules\Locations\Domain\Events;

final class LocationCreated
{
    public function __construct(
        public readonly string $locationId,
        public readonly string $label,
        public readonly string $warehouseCode,
        public readonly string $occurredAt,
        public readonly ?string $actorId = null,
    ) {
    }
}
