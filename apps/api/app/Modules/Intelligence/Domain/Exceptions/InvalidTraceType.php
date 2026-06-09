<?php

declare(strict_types=1);

namespace App\Modules\Intelligence\Domain\Exceptions;

use RuntimeException;

final class InvalidTraceType extends RuntimeException
{
    public static function withType(string $type): self
    {
        return new self(sprintf('Invalid trace type: [%s].', $type));
    }
}
