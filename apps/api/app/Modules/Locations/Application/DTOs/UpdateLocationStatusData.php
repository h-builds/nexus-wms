<?php

declare(strict_types=1);

namespace App\Modules\Locations\Application\DTOs;

final readonly class UpdateLocationStatusData
{
    public function __construct(
        public string $locationId,
        public bool $isBlocked,
        public ?string $reason,
        public string $performedBy,
    ) {}
}
