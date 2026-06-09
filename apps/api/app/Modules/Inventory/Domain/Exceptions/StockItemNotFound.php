<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Domain\Exceptions;

use RuntimeException;

final class StockItemNotFound extends RuntimeException
{
    public static function withId(string $id): self
    {
        return new self("StockItem with id [{$id}] was not found.");
    }

    public static function forProductAndLocation(string $productId, string $locationId): self
    {
        return new self("StockItem not found for product [{$productId}] at location [{$locationId}].");
    }
}
