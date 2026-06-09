<?php

declare(strict_types=1);

namespace App\Modules\Locations\Application\DTOs;

use App\Modules\Locations\Domain\Exceptions\InvalidLocationPayload;

final readonly class UpdateLocationStatusDTO
{
    public function __construct(
        public string $locationId,
        public bool $isBlocked,
        public ?string $reason,
        public string $performedBy,
        public string $correlationId,
    ) {
        if (trim($this->locationId) === '') throw InvalidLocationPayload::requiredFieldEmpty('Location ID');
        if (trim($this->performedBy) === '') throw InvalidLocationPayload::requiredFieldEmpty('Performed By');
        if (trim($this->correlationId) === '') throw InvalidLocationPayload::requiredFieldEmpty('Correlation ID');
        if ($this->isBlocked && trim((string)$this->reason) === '') {
            throw InvalidLocationPayload::reasonRequiredForBlock();
        }
    }
}
