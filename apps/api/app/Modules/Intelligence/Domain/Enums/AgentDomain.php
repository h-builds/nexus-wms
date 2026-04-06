<?php

declare(strict_types=1);

namespace App\Modules\Intelligence\Domain\Enums;

use App\Modules\Intelligence\Domain\Exceptions\InvalidAgentDomain;

enum AgentDomain: string
{
    case Inventory = 'inventory';
    case Incidents = 'incidents';
    case Movements = 'movements';
    case Monitoring = 'monitoring';

    public static function fromValue(string $value): self
    {
        $enum = self::tryFrom($value);

        if ($enum === null) {
            throw InvalidAgentDomain::withDomain($value);
        }

        return $enum;
    }
}
