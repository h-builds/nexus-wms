<?php

declare(strict_types=1);

namespace App\Modules\Movements\Domain\Enums;

enum MovementType: string
{
    case RECEIPT = 'receipt';
    case PUTAWAY = 'putaway';
    case RELOCATION = 'relocation';
    case ADJUSTMENT = 'adjustment';
    case PICKING = 'picking';
    case RETURN_INTERNAL = 'return_internal';

    public function requiresFromLocation(): bool
    {
        return match($this) {
            self::PUTAWAY, self::RELOCATION, self::ADJUSTMENT, self::PICKING, self::RETURN_INTERNAL => true,
            self::RECEIPT => false,
        };
    }

    public function requiresToLocation(): bool
    {
        return match($this) {
            self::RECEIPT, self::PUTAWAY, self::RELOCATION, self::RETURN_INTERNAL => true,
            self::ADJUSTMENT, self::PICKING => false,
        };
    }

    public function forbidsSameLocations(): bool
    {
        return match($this) {
            self::PUTAWAY, self::RELOCATION, self::RETURN_INTERNAL => true,
            default => false,
        };
    }
}
