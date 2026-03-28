<?php

declare(strict_types=1);

namespace App\Modules\Product\Application\DTOs;

final class CreateProductData
{
    public function __construct(
        public readonly string $sku,
        public readonly string $name,
        public readonly string $category,
        public readonly string $unitOfMeasure,
        public readonly array $attributes = [],
        public readonly ?string $actorId = null,
    ) {
    }

    /**
     * @param array<string, mixed> $input
     */
    public static function fromArray(array $input): self
    {
        return new self(
            sku: (string) $input['sku'],
            name: (string) $input['name'],
            category: (string) $input['category'],
            unitOfMeasure: (string) $input['unitOfMeasure'],
            attributes: isset($input['attributes']) && is_array($input['attributes']) ? $input['attributes'] : [],
            actorId: isset($input['actorId']) ? (string) $input['actorId'] : null,
        );
    }
}