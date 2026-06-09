<?php

declare(strict_types=1);

namespace App\Modules\Incidents\Domain\Exceptions;

use DomainException;

final class InvalidIncidentSeverity extends DomainException
{
    public static function withSeverity(string $severity): self
    {
        return new self(sprintf('Invalid incident severity: [%s].', $severity));
    }
}
