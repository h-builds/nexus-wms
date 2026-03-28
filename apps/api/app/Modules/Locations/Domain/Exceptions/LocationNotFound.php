<?php

declare(strict_types=1);

namespace App\Modules\Locations\Domain\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

final class LocationNotFound extends RuntimeException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Location with ID [%s] not found.', $id));
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => 'location_not_found',
                'message' => $this->getMessage(),
            ],
        ], 404);
    }
}
