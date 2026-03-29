<?php

declare(strict_types=1);

namespace App\Modules\Movements\Domain\Services;

use App\Modules\Movements\Domain\Enums\MovementType;
use InvalidArgumentException;

/**
 * Domain service for movement validation logic.
 * Encapsulates cross-domain constraints like checking location block status before performing logic.
 */
final class MovementValidator
{
    public function assertLocationNotBlocked(string $locationId, bool $isBlocked): void
    {
        if ($isBlocked) {
            throw new InvalidArgumentException("Location {$locationId} is blocked.");
        }
    }

    /**
     * Compute what the adjustment delta available will be for the given Movement.
     * We don't mutate here; we just compute what we give to InventoryValidator.
     */
    public function computeAvailableDelta(MovementType $type, int $quantity): int
    {
        return match ($type) {
            MovementType::RECEIPT, MovementType::RETURN_INTERNAL => $quantity,
            MovementType::PICKING, MovementType::ADJUSTMENT => -$quantity,
            default => 0, // Putaway/Relocation don't change global available, they just change location or blocked states.
        };
    }
}
