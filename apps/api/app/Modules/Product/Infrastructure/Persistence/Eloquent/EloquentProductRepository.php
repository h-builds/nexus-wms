<?php

declare(strict_types=1);

namespace App\Modules\Product\Infrastructure\Persistence\Eloquent;

use App\Modules\Product\Domain\Entities\Product;
use App\Modules\Product\Domain\Enums\UnitOfMeasure;
use App\Modules\Product\Domain\Repositories\ProductRepository;

final class EloquentProductRepository implements ProductRepository
{
    public function create(Product $product): Product
    {
        ProductModel::query()->create([
            'id' => $product->id(),
            'sku' => $product->sku(),
            'name' => $product->name(),
            'category' => $product->category(),
            'unit_of_measure' => $product->unitOfMeasure()->value,
            'attributes' => $product->attributes(),
        ]);

        return $product;
    }

    public function findById(string $id): ?Product
    {
        $model = ProductModel::query()->find($id);

        return $model ? $this->toDomain($model) : null;
    }

    public function findBySku(string $sku): ?Product
    {
        $model = ProductModel::query()
            ->where('sku', $sku)
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function all(): array
    {
        return ProductModel::query()
            ->orderBy('name')
            ->get()
            ->map(fn (ProductModel $model): Product => $this->toDomain($model))
            ->all();
    }

    private function toDomain(ProductModel $model): Product
    {
        return new Product(
            id: (string) $model->getAttribute('id'),
            sku: (string) $model->getAttribute('sku'),
            name: (string) $model->getAttribute('name'),
            category: (string) $model->getAttribute('category'),
            unitOfMeasure: UnitOfMeasure::from((string) $model->getAttribute('unit_of_measure')),
            attributes: is_array($model->getAttribute('attributes')) ? $model->getAttribute('attributes') : [],
        );
    }
}