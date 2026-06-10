<?php

declare(strict_types=1);

namespace App\Modules\Product\Application\DTOs;

final class CreateProductPayload
{
    public function __construct(
        public readonly string $sku,
        public readonly string $name,
        public readonly string $category,
        public readonly string $unitOfMeasure,
        public readonly array $attributes = [],
        public readonly ?string $actorId = null,
        public readonly ?string $correlationId = null,
    ) {}

    public static function fromArray(array $payload): self
    {
        return new self(
            sku: (string) ($payload['sku'] ?? ''),
            name: (string) ($payload['name'] ?? ''),
            category: (string) ($payload['category'] ?? ''),
            unitOfMeasure: (string) ($payload['unitOfMeasure'] ?? ''),
            attributes: is_array($payload['attributes'] ?? null) ? $payload['attributes'] : [],
            actorId: isset($payload['actorId']) ? (string) $payload['actorId'] : null,
            correlationId: isset($payload['correlationId']) ? (string) $payload['correlationId'] : null,
        );
    }
}
