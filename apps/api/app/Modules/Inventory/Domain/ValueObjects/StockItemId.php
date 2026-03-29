<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Domain\ValueObjects;

use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

readonly class StockItemId
{
    public function __construct(public string $value)
    {
        if (!Uuid::isValid($value)) {
            throw new InvalidArgumentException('Invalid StockItem ID.');
        }
    }

    public static function generate(): self
    {
        return new self(Uuid::uuid4()->toString());
    }
}
