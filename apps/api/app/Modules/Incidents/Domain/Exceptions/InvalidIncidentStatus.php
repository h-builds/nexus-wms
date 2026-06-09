<?php

declare(strict_types=1);

namespace App\Modules\Incidents\Domain\Exceptions;

use DomainException;

final class InvalidIncidentStatus extends DomainException
{
    public static function withStatus(string $status): self
    {
        return new self(sprintf('Invalid incident status: [%s].', $status));
    }
}
