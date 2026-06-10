<?php

declare(strict_types=1);

namespace App\Modules\Movements\Application\DTOs;

final readonly class RegisterMovementDTO
{
    public function __construct(
        public string $productId,
        public ?string $fromLocationId,
        public ?string $toLocationId,
        public string $type,
        public int $quantity,
        public ?string $reference,
        public ?string $lotNumber,
        public ?string $reason,
        public string $performedBy,
        public string $performedAt,
        public string $correlationId,
        public ?string $idempotencyKey = null,
    ) {
        if ($this->quantity <= 0) {
            throw new \InvalidArgumentException('Movement quantity must be greater than zero.');
        }

        if ($this->fromLocationId === null && $this->toLocationId === null) {
            throw new \InvalidArgumentException('Movement must have a source or destination location.');
        }

        if (empty(trim($this->productId))) {
            throw new \InvalidArgumentException('Product ID cannot be empty.');
        }

        if (empty(trim($this->type))) {
            throw new \InvalidArgumentException('Movement type cannot be empty.');
        }
    }
}
