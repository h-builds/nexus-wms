<?php

declare(strict_types=1);

namespace App\Modules\Movements\Application\DTOs;

use App\Modules\Events\Domain\DTOs\DomainEventPayload;

final readonly class StockAdjustedEventPayload implements DomainEventPayload
{
    public function __construct(
        public string $productId,
        public ?string $locationId,
        public int $previousQuantity,
        public int $newQuantity,
        public ?string $reason,
    ) {}

    public function toArray(): array
    {
        return [
            'productId' => $this->productId,
            'locationId' => $this->locationId,
            'previousQuantity' => $this->previousQuantity,
            'newQuantity' => $this->newQuantity,
            'reason' => $this->reason,
        ];
    }
}
