<?php

declare(strict_types=1);

namespace App\Modules\Movements\Application\DTOs;

use App\Modules\Events\Domain\DTOs\DomainEventPayload;

final readonly class MovementCreatedEventPayload implements DomainEventPayload
{
    public function __construct(
        public string $movementId,
        public string $productId,
        public string $type,
        public int $quantity,
        public ?string $fromLocationId,
        public ?string $toLocationId,
    ) {}

    public function toArray(): array
    {
        return [
            'movementId' => $this->movementId,
            'productId' => $this->productId,
            'type' => $this->type,
            'quantity' => $this->quantity,
            'fromLocationId' => $this->fromLocationId,
            'toLocationId' => $this->toLocationId,
        ];
    }
}
