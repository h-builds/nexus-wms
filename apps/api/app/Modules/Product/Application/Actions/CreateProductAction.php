<?php

declare(strict_types=1);

namespace App\Modules\Product\Application\Actions;

use App\Modules\Events\Application\Services\EventPublisher;
use App\Modules\Product\Application\DTOs\CreateProductData;
use App\Modules\Product\Domain\Entities\Product;
use App\Modules\Product\Domain\Enums\UnitOfMeasure;
use App\Modules\Product\Domain\Exceptions\DuplicateSku;
use App\Modules\Product\Domain\Exceptions\InvalidUnitOfMeasure;
use App\Modules\Product\Domain\Repositories\ProductRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CreateProductAction
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly EventPublisher $eventPublisher,
    ) {}

    public function execute(CreateProductData $command): Product
    {
        $this->ensureSkuIsUnique($command->sku);

        $newProduct = $this->buildProductEntity($command);

        $correlationId = $command->correlationId ?? Str::uuid()->toString();

        return $this->persistProductAndEvents($newProduct, $command->actorId, $correlationId);
    }

    private function ensureSkuIsUnique(string $sku): void
    {
        $existingProduct = $this->products->findBySku($sku);

        if ($existingProduct !== null) {
            throw DuplicateSku::withSku($sku);
        }
    }

    private function buildProductEntity(CreateProductData $command): Product
    {
        $unitOfMeasure = UnitOfMeasure::tryFrom($command->unitOfMeasure);
        
        if (!$unitOfMeasure) {
            throw InvalidUnitOfMeasure::withUnit($command->unitOfMeasure);
        }

        return new Product(
            id: (string) Str::uuid(),
            sku: $command->sku,
            name: $command->name,
            category: $command->category,
            unitOfMeasure: $unitOfMeasure,
            attributes: $command->attributes,
        );
    }

    private function persistProductAndEvents(Product $newProduct, string $actorId, string $correlationId): Product
    {
        return DB::transaction(function () use ($newProduct, $actorId, $correlationId) {
            $persistedProduct = $this->products->create($newProduct);

            $this->publishProductCreatedEvent($persistedProduct, $actorId, $correlationId);

            return $persistedProduct;
        });
    }

    private function publishProductCreatedEvent(Product $persistedProduct, string $actorId, string $correlationId): void
    {
        $this->eventPublisher->publish(
            eventType: 'product.created',
            payload: [
                'productId' => $persistedProduct->id(),
                'sku' => $persistedProduct->sku(),
                'name' => $persistedProduct->name(),
                'category' => $persistedProduct->category(),
                'unitOfMeasure' => $persistedProduct->unitOfMeasure()->value,
            ],
            actorId: $actorId,
            correlationId: $correlationId
        );
    }
}