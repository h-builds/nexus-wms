<?php

declare(strict_types=1);

namespace App\Modules\Locations\Domain\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

final class DuplicateLocationLabel extends RuntimeException
{
    public static function withLabel(string $label): self
    {
        return new self(sprintf('Location with label [%s] already exists.', $label));
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => 'duplicate_location_label',
                'message' => $this->getMessage(),
            ],
        ], 409);
    }
}
