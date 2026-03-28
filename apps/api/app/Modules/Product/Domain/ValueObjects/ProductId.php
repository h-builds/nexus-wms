<?php

namespace App\Modules\Product\Domain\ValueObjects;

use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

readonly class ProductId
{
    public function __construct(public string $value)
    {
        if (!Uuid::isValid($value)) {
            throw new InvalidArgumentException('Invalid Product ID');
        }
    }

    public static function generate(): self
    {
        return new self(Uuid::uuid4()->toString());
    }
}
