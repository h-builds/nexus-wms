<?php

declare(strict_types=1);

namespace App\Modules\Product\Domain\Exceptions;

use RuntimeException;

final class DuplicateSku extends RuntimeException
{
    public static function withSku(string $sku): self
    {
        return new self("Product with sku [{$sku}] already exists.");
    }

    public function render($request)
    {
        return response()->json([
            'error' => [
                'code' => 'duplicate_sku',
                'message' => $this->getMessage(),
            ],
        ], 409);
    }
}