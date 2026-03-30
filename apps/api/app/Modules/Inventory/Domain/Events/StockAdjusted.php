<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Domain\Events;

final class StockAdjusted
{
    public function __construct(
        public readonly string $productId,
        public readonly string $locationId,
        public readonly int $previousQuantity,
        public readonly int $newQuantity,
        public readonly string $reason,
        public readonly string $occurredAt,
        public readonly ?string $actorId = null,
        public readonly ?string $correlationId = null,
    ) {
    }
}
