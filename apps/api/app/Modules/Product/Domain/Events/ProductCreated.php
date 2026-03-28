<?php

declare(strict_types=1);

namespace App\Modules\Product\Domain\Events;

final class ProductCreated
{
    public function __construct(
        public readonly string $productId,
        public readonly string $sku,
        public readonly string $name,
        public readonly string $category,
        public readonly string $unitOfMeasure,
        public readonly string $occurredAt,
        public readonly ?string $actorId = null,
    ) {
    }
}