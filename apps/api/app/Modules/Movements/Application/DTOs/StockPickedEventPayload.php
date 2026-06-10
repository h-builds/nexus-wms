<?php

declare(strict_types=1);

namespace App\Modules\Movements\Application\DTOs;

use App\Modules\Events\Domain\DTOs\DomainEventPayload;

final readonly class StockPickedEventPayload implements DomainEventPayload
{
    public function __construct(
        public string $productId,
        public ?string $locationId,
        public int $quantity,
    ) {}

    public function toArray(): array
    {
        return [
            'productId' => $this->productId,
            'locationId' => $this->locationId,
            'quantity' => $this->quantity,
        ];
    }
}
