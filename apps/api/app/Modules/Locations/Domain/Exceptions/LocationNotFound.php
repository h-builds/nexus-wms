<?php

declare(strict_types=1);

namespace App\Modules\Locations\Domain\Exceptions;

use RuntimeException;

final class LocationNotFound extends RuntimeException
{
    public static function withId(string $id): self
    {
        return new self("Location with id [{$id}] was not found.");
    }

    public function render($request)
    {
        return response()->json([
            'error' => [
                'code' => 'location_not_found',
                'message' => $this->getMessage(),
            ],
        ], 404);
    }
}
