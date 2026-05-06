<?php

declare(strict_types=1);

namespace App\Modules\Locations\Application\DTOs;

use App\Modules\Locations\Domain\Exceptions\InvalidLocationData;

final readonly class CreateLocationDTO
{
    public function __construct(
        public string $warehouseCode,
        public string $zone,
        public string $aisle,
        public string $rack,
        public string $level,
        public string $bin,
        public string $correlationId,
        public ?string $actorId = null,
    ) {
        if (trim($this->warehouseCode) === '') throw InvalidLocationData::requiredFieldEmpty('Warehouse code');
        if (trim($this->zone) === '') throw InvalidLocationData::requiredFieldEmpty('Zone');
        if (trim($this->aisle) === '') throw InvalidLocationData::requiredFieldEmpty('Aisle');
        if (trim($this->rack) === '') throw InvalidLocationData::requiredFieldEmpty('Rack');
        if (trim($this->level) === '') throw InvalidLocationData::requiredFieldEmpty('Level');
        if (trim($this->bin) === '') throw InvalidLocationData::requiredFieldEmpty('Bin');
        if (trim($this->correlationId) === '') throw InvalidLocationData::requiredFieldEmpty('Correlation ID');
    }
}
