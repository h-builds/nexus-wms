<?php

declare(strict_types=1);

namespace App\Modules\Product\Domain\Exceptions;

use RuntimeException;

final class InvalidUnitOfMeasure extends RuntimeException
{
    public static function withUnit(string $unit): self
    {
        return new self("Invalid unit of measure: {$unit}.");
    }
}
