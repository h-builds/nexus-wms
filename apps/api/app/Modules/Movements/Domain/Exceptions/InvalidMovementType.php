<?php

declare(strict_types=1);

namespace App\Modules\Movements\Domain\Exceptions;

use DomainException;

final class InvalidMovementType extends DomainException
{
    public static function withType(string $type): self
    {
        return new self("Invalid movement type: {$type}.");
    }
}
