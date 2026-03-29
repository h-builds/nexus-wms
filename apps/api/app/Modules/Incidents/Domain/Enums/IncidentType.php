<?php

declare(strict_types=1);

namespace App\Modules\Incidents\Domain\Enums;

enum IncidentType: string
{
    case DAMAGE = 'damage';
    case SHORTAGE = 'shortage';
    case OVERAGE = 'overage';
    case EXPIRATION = 'expiration';
    case MISPLACEMENT = 'misplacement';
    case BROKEN_PACKAGING = 'broken_packaging';
    case NONCONFORMING_PRODUCT = 'nonconforming_product';
    case PICKING_BLOCKER = 'picking_blocker';
    case LOT_ERROR = 'lot_error';
}
