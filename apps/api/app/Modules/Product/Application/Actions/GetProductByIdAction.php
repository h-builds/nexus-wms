<?php

declare(strict_types=1);

namespace App\Modules\Product\Application\Actions;

use App\Modules\Product\Domain\Entities\Product;
use App\Modules\Product\Domain\Exceptions\ProductNotFound;
use App\Modules\Product\Domain\Repositories\ProductRepository;

final class GetProductByIdAction
{
    public function __construct(
        private readonly ProductRepository $products,
    ) {
    }

    public function execute(string $id): Product
    {
        $product = $this->products->findById($id);

        if ($product === null) {
            throw ProductNotFound::withId($id);
        }

        return $product;
    }
}