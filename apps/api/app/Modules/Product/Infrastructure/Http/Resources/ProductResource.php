<?php

declare(strict_types=1);

namespace App\Modules\Product\Infrastructure\Http\Resources;

use App\Modules\Product\Domain\Entities\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 */
final class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Product $product */
        $product = $this->resource;

        return [
            'id' => $product->id(),
            'sku' => $product->sku(),
            'name' => $product->name(),
            'category' => $product->category(),
            'unitOfMeasure' => $product->unitOfMeasure()->value,
            'attributes' => $product->attributes(),
        ];
    }
}