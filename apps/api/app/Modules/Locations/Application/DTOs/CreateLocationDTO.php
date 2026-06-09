<?php

declare(strict_types=1);

namespace App\Modules\Locations\Application\DTOs;

use App\Modules\Locations\Domain\Exceptions\InvalidLocationPayload;

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
        if (trim($this->warehouseCode) === '') throw InvalidLocationPayload::requiredFieldEmpty('Warehouse code');
        if (trim($this->zone) === '') throw InvalidLocationPayload::requiredFieldEmpty('Zone');
        if (trim($this->aisle) === '') throw InvalidLocationPayload::requiredFieldEmpty('Aisle');
        if (trim($this->rack) === '') throw InvalidLocationPayload::requiredFieldEmpty('Rack');
        if (trim($this->level) === '') throw InvalidLocationPayload::requiredFieldEmpty('Level');
        if (trim($this->bin) === '') throw InvalidLocationPayload::requiredFieldEmpty('Bin');
        if (trim($this->correlationId) === '') throw InvalidLocationPayload::requiredFieldEmpty('Correlation ID');
    }
}
