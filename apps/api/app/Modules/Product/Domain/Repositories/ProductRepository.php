<?php

declare(strict_types=1);

namespace App\Modules\Product\Domain\Repositories;

use App\Modules\Product\Domain\Entities\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProductRepository
{
    public function create(Product $product): Product;

    public function findById(string $id): ?Product;

    public function findBySku(string $sku): ?Product;

    public function paginate(int $page = 1, int $perPage = 50, array $filters = []): LengthAwarePaginator;
}