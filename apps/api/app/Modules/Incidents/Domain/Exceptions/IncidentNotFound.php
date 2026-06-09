<?php

declare(strict_types=1);

namespace App\Modules\Incidents\Domain\Exceptions;

use RuntimeException;

final class IncidentNotFound extends RuntimeException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Incident with ID [%s] not found.', $id));
    }
}
