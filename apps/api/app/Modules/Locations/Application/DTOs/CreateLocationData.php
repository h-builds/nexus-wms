<?php

declare(strict_types=1);

namespace App\Modules\Locations\Application\DTOs;

final class CreateLocationData
{
    public function __construct(
        public readonly string $warehouseCode,
        public readonly string $zone,
        public readonly string $aisle,
        public readonly string $rack,
        public readonly string $level,
        public readonly string $bin,
        public readonly ?string $actorId = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            warehouseCode: $data['warehouseCode'],
            zone: $data['zone'],
            aisle: $data['aisle'],
            rack: $data['rack'],
            level: $data['level'],
            bin: $data['bin'],
            actorId: $data['actorId'] ?? null,
        );
    }
}
