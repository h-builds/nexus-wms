<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Domain\Events;

/**
 * Event contract: inventory.stock.relocated
 *
 * Emitted when stock is moved between locations.
 * Not dispatched in the Inventory-only phase — prepared for Movements integration.
 */
final class StockRelocated
{
    public function __construct(
        public readonly string $productId,
        public readonly string $fromLocationId,
        public readonly string $toLocationId,
        public readonly int $quantity,
        public readonly string $occurredAt,
        public readonly ?string $actorId = null,
        public readonly ?string $correlationId = null,
    ) {
    }
}
