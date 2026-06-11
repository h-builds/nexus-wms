<?php

declare(strict_types=1);

namespace App\Modules\Locations\Application\DTOs;

use App\Modules\Events\Domain\DTOs\DomainEventPayload;

final class LocationStatusUpdatedEventPayload implements DomainEventPayload
{
    public function __construct(
        public readonly string $locationId,
        public readonly bool $isBlocked,
        public readonly ?string $reason = null
    ) {}

    public function toArray(): array
    {
        $payload = [
            'locationId' => $this->locationId,
            'isBlocked' => $this->isBlocked,
        ];

        if ($this->reason !== null) {
            $payload['reason'] = $this->reason;
        }

        return $payload;
    }
}
