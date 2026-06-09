<?php

declare(strict_types=1);

namespace App\Modules\Locations\Domain\Exceptions;

use RuntimeException;

final class InvalidLocationPayload extends RuntimeException
{
    public static function requiredFieldEmpty(string $field): self
    {
        return new self(sprintf('%s cannot be empty.', $field));
    }

    public static function reasonRequiredForBlock(): self
    {
        return new self('Reason is required when blocking a location.');
    }
}
