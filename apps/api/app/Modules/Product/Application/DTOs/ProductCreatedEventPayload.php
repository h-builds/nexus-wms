<?php

declare(strict_types=1);

namespace App\Modules\Product\Application\DTOs;

use App\Modules\Events\Domain\DTOs\DomainEventPayload;

final class ProductCreatedEventPayload implements DomainEventPayload
{
    public function __construct(
        public readonly string $productId,
        public readonly string $sku,
        public readonly string $name,
        public readonly string $category,
        public readonly string $unitOfMeasure,
    ) {}

    public function toArray(): array
    {
        return [
            'productId' => $this->productId,
            'sku' => $this->sku,
            'name' => $this->name,
            'category' => $this->category,
            'unitOfMeasure' => $this->unitOfMeasure,
        ];
    }
}
