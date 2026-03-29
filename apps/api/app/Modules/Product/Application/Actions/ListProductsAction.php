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

    public function execute(int $page = 1, int $perPage = 50, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->products->paginate($page, $perPage, $filters);
    }
}