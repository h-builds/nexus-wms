<?php

declare(strict_types=1);

namespace App\Modules\Movements\Application\DTOs;

final class RegisterMovementDTO
{
    public function __construct(
        public readonly string $productId,
        public readonly ?string $fromLocationId,
        public readonly ?string $toLocationId,
        public readonly string $type,
        public readonly int $quantity,
        public readonly ?string $reference,
        public readonly ?string $lotNumber,
        public readonly ?string $reason,
        public readonly string $performedBy,
        public readonly string $performedAt,
        public readonly ?string $idempotencyKey = null,
    ) {}
}
