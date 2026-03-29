<?php

declare(strict_types=1);

namespace App\Modules\Incidents\Domain\Enums;

enum IncidentSeverity: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
}
