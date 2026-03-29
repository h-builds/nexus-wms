<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Domain\Events;

/**
 * Event contract: inventory.stock.received
 *
 * Emitted when new stock enters the system via a receipt movement.
 * Not dispatched in the Inventory-only phase — prepared for Movements integration.
 */
final class StockReceived
{
    public function __construct(
        public readonly string $movementId,
        public readonly string $productId,
        public readonly string $locationId,
        public readonly int $quantity,
        public readonly ?string $lotNumber,
        public readonly string $occurredAt,
        public readonly ?string $actorId = null,
        public readonly ?string $correlationId = null,
    ) {
    }
}
