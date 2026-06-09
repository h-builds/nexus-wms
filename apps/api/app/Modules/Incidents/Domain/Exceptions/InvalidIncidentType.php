<?php

declare(strict_types=1);

namespace App\Modules\Incidents\Domain\Exceptions;

use DomainException;

final class InvalidIncidentType extends DomainException
{
    public static function withType(string $type): self
    {
        return new self(sprintf('Invalid incident type: [%s].', $type));
    }
}
