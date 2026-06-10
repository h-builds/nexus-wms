<?php

declare(strict_types=1);

namespace App\Modules\Movements\Application\DTOs;

use App\Modules\Events\Domain\DTOs\DomainEventPayload;

final readonly class StockMovedEventPayload implements DomainEventPayload
{
    public function __construct(
        public string $productId,
        public ?string $fromLocationId,
        public ?string $toLocationId,
        public int $quantity,
    ) {}

    public function toArray(): array
    {
        return [
            'productId' => $this->productId,
            'fromLocationId' => $this->fromLocationId,
            'toLocationId' => $this->toLocationId,
            'quantity' => $this->quantity,
        ];
    }
}
