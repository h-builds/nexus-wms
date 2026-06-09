<?php

declare(strict_types=1);

namespace App\Modules\Intelligence\Domain\Exceptions;

use RuntimeException;

final class DecisionTraceNotFound extends RuntimeException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Decision trace with ID [%s] not found.', $id));
    }
}
