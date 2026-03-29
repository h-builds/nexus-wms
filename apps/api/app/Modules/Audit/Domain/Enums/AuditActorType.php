<?php

declare(strict_types=1);

namespace App\Modules\Audit\Domain\Enums;

enum AuditActorType: string
{
    case HUMAN = 'human';
    case SYSTEM = 'system';
    case AGENT = 'agent';
}
