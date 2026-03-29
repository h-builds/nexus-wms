<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Domain\Services;

use InvalidArgumentException;

/**
 * Domain service for inventory validation logic.
 *
 * Encapsulates pure business rules that don't belong to a single entity.
 * Used by the application layer and (future) Movements domain for pre-mutation validation.
 */
final class InventoryValidator
{
    /**
     * Validate that a proposed quantity adjustment would not violate stock invariants.
     *
     * @throws InvalidArgumentException If the resulting state would violate invariants
     */
    public function validateAdjustment(
        int $currentAvailable,
        int $currentBlocked,
        int $deltaAvailable,
        int $deltaBlocked,
    ): void {
        $newAvailable = $currentAvailable + $deltaAvailable;
        $newBlocked = $currentBlocked + $deltaBlocked;

        if ($newAvailable < 0) {
            throw new InvalidArgumentException(
                "Insufficient available quantity at source location. Result would be negative ({$newAvailable})."
            );
        }

        if ($newBlocked < 0) {
            throw new InvalidArgumentException(
                "Insufficient blocked quantity at source location. Result would be negative ({$newBlocked})."
            );
        }
    }

    public function computeOnHand(int $available, int $blocked): int
    {
        return $available + $blocked;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function validateDerivation(int $onHand, int $available, int $blocked): void
    {
        $expected = $available + $blocked;

        if ($onHand !== $expected) {
            throw new InvalidArgumentException(
                "quantityOnHand ({$onHand}) does not equal quantityAvailable ({$available}) + quantityBlocked ({$blocked}) = {$expected}."
            );
        }
    }
}
