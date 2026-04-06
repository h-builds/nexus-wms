<?php

declare(strict_types=1);

namespace App\Modules\Intelligence\Domain\Enums;

enum TraceStatus: string
{
    case Advisory = 'advisory';
    case Acknowledged = 'acknowledged';
    case ActedUpon = 'acted_upon';
    case Dismissed = 'dismissed';

    /**
     * Status can only move forward, never backward.
     * Advisory → Acknowledged | ActedUpon | Dismissed
     * Acknowledged → ActedUpon | Dismissed
     * ActedUpon and Dismissed are terminal states.
     */
    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Advisory => in_array($target, [self::Acknowledged, self::ActedUpon, self::Dismissed], true),
            self::Acknowledged => in_array($target, [self::ActedUpon, self::Dismissed], true),
            self::ActedUpon, self::Dismissed => false,
        };
    }
}
