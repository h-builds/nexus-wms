<?php

declare(strict_types=1);

namespace App\Modules\Locations\Application\DTOs;

final readonly class LocationListCriteria
{
    public function __construct(
        public ?string $warehouseCode = null,
        public ?string $zone = null,
        public ?string $aisle = null,
        public ?string $rack = null,
        public ?string $bin = null,
    ) {
    }
}
