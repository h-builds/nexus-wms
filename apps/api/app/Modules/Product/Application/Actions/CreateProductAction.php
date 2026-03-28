<?php

declare(strict_types=1);

namespace App\Modules\Product\Application\Actions;

use App\Modules\Product\Application\DTOs\CreateProductData;
use App\Modules\Product\Domain\Entities\Product;
use App\Modules\Product\Domain\Enums\UnitOfMeasure;
use App\Modules\Product\Domain\Events\ProductCreated;
use App\Modules\Product\Domain\Exceptions\DuplicateSku;
use App\Modules\Product\Domain\Repositories\ProductRepository;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

final class CreateProductAction
{
    public function __construct(
        private readonly ProductRepository $products,
    ) {
    }

    public function execute(CreateProductData $data): Product
    {
        $existing = $this->products->findBySku($data->sku);

        if ($existing !== null) {
            throw DuplicateSku::withSku($data->sku);
        }

        $product = new Product(
            id: (string) Str::uuid(),
            sku: $data->sku,
            name: $data->name,
            category: $data->category,
            unitOfMeasure: UnitOfMeasure::from($data->unitOfMeasure),
            attributes: $data->attributes,
        );

        $created = $this->products->create($product);

        Event::dispatch(new ProductCreated(
            productId: $created->id(),
            sku: $created->sku(),
            name: $created->name(),
            category: $created->category(),
            unitOfMeasure: $created->unitOfMeasure()->value,
            occurredAt: now()->toIso8601String(),
            actorId: $data->actorId,
        ));

        return $created;
    }
}