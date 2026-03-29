<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Domain\Enums;

enum InventoryStatus: string
{
    case AVAILABLE = 'available';
    case BLOCKED = 'blocked';
    case IN_TRANSIT = 'in_transit';
    case QUARANTINE = 'quarantine';
}
