<?php

declare(strict_types=1);

namespace App\Modules\Product\Application\DTOs;

final class ProductView
{
    public function __construct(
        public readonly string $id,
        public readonly string $sku,
        public readonly string $name,
        public readonly string $category,
        public readonly string $unitOfMeasure,
        public readonly array $attributes,
    ) {
    }
}