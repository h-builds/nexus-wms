<?php

declare(strict_types=1);

namespace App\Modules\Product\Application\Actions;

use App\Modules\Product\Domain\Repositories\ProductRepository;

final class ListProductsAction
{
    public function __construct(
        private readonly ProductRepository $products,
    ) {
    }

    public function execute(): array
    {
        return $this->products->all();
    }
}