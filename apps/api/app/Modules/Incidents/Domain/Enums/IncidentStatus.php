<?php

declare(strict_types=1);

namespace App\Modules\Incidents\Domain\Enums;

enum IncidentStatus: string
{
    case OPEN = 'open';
    case IN_REVIEW = 'in_review';
    case RESOLVED = 'resolved';
    case CLOSED = 'closed';

    public function canTransitionTo(self $newStatus): bool
    {
        return match ($this) {
            self::OPEN => in_array($newStatus, [self::IN_REVIEW, self::CLOSED], true),
            self::IN_REVIEW => in_array($newStatus, [self::RESOLVED, self::CLOSED], true),
            self::RESOLVED => $newStatus === self::CLOSED,
            self::CLOSED => false,
        };
    }
}
