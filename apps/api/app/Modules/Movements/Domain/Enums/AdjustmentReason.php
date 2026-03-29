<?php

declare(strict_types=1);

namespace App\Modules\Movements\Domain\Enums;

enum AdjustmentReason: string
{
    case MANUAL_ADJUSTMENT = 'manual_adjustment';
    case CYCLE_COUNT = 'cycle_count';
    case INCIDENT_DAMAGE = 'incident_damage';
    case INCIDENT_SHORTAGE = 'incident_shortage';
    case QUALITY_HOLD = 'quality_hold';
    case CORRECTION = 'correction';

}
