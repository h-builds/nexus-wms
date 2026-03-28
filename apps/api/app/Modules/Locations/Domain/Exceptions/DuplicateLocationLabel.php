<?php

declare(strict_types=1);

namespace App\Modules\Locations\Domain\Exceptions;

use RuntimeException;

final class DuplicateLocationLabel extends RuntimeException
{
    public static function withLabel(string $label): self
    {
        return new self("Location with label [{$label}] already exists.");
    }

    public function render($request)
    {
        return response()->json([
            'error' => [
                'code' => 'duplicate_location_label',
                'message' => $this->getMessage(),
            ],
        ], 409);
    }
}
