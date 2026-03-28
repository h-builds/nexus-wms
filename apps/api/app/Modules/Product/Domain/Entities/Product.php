<?php

declare(strict_types=1);

namespace App\Modules\Product\Domain\Entities;

use App\Modules\Product\Domain\Enums\UnitOfMeasure;

final class Product
{
    public function __construct(
        private readonly string $id,
        private readonly string $sku,
        private readonly string $name,
        private readonly string $category,
        private readonly UnitOfMeasure $unitOfMeasure,
        private readonly array $attributes = [],
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function sku(): string
    {
        return $this->sku;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function category(): string
    {
        return $this->category;
    }

    public function unitOfMeasure(): UnitOfMeasure
    {
        return $this->unitOfMeasure;
    }

    public function attributes(): array
    {
        return $this->attributes;
    }
}