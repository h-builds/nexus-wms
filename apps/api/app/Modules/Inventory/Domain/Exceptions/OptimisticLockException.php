<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Domain\Exceptions;

use RuntimeException;

final class OptimisticLockException extends RuntimeException
{
    public static function forStockItem(string $id): self
    {
        return new self("StockItem [{$id}] was modified by another operation. Please retry.");
    }
}
