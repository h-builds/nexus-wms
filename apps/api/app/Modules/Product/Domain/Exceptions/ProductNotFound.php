<?php

declare(strict_types=1);

namespace App\Modules\Product\Domain\Exceptions;

use RuntimeException;

final class ProductNotFound extends RuntimeException
{
    public static function withId(string $id): self
    {
        return new self("Product with id [{$id}] was not found.");
    }

    public function render($request)
    {
        return response()->json([
            'message' => $this->getMessage(),
        ], 404);
    }
}