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

    public function render($request)
    {
        return response()->json([
            'error' => [
                'code' => 'stock_item_not_found',
                'message' => $this->getMessage(),
            ],
        ], 404);
    }
}
