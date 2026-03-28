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

    /**
     * @param array<string, mixed> $input
     */
    public static function fromArray(array $input): self
    {
        return new self(
            warehouseCode: (string) $input['warehouseCode'],
            zone: (string) $input['zone'],
            aisle: (string) $input['aisle'],
            rack: (string) $input['rack'],
            level: (string) $input['level'],
            bin: (string) $input['bin'],
            actorId: isset($input['actorId']) ? (string) $input['actorId'] : null,
        );
    }
}
