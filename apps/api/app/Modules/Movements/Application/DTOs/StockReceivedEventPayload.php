<?php

declare(strict_types=1);

namespace App\Modules\Movements\Application\DTOs;

use App\Modules\Events\Domain\DTOs\DomainEventPayload;

final readonly class StockReceivedEventPayload implements DomainEventPayload
{
    public function __construct(
        public string $movementId,
        public string $productId,
        public ?string $locationId,
        public int $quantity,
        public ?string $lotNumber,
    ) {}

    public function toArray(): array
    {
        return [
            'movementId' => $this->movementId,
            'productId' => $this->productId,
            'locationId' => $this->locationId,
            'quantity' => $this->quantity,
            'lotNumber' => $this->lotNumber,
        ];
    }
}
