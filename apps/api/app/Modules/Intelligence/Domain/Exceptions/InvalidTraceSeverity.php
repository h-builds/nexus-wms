<?php

declare(strict_types=1);

namespace App\Modules\Intelligence\Domain\Exceptions;

use RuntimeException;

final class InvalidTraceSeverity extends RuntimeException
{
    public static function withSeverity(string $severity): self
    {
        return new self(sprintf('Invalid trace severity: [%s].', $severity));
    }
}
