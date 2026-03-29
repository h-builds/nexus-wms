<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Domain\Repositories;

use App\Modules\Inventory\Domain\Entities\StockItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface StockItemRepository
{
    public function findById(string $id): ?StockItem;

    /**
     * Paginate stock items with optional filters.
     *
     * Supported filter keys:
     *  - productId (string)
     *  - locationId (string)
     *  - status (string)
     *
     * @param array<string, mixed> $filters
     */
    public function paginate(int $page = 1, int $perPage = 50, array $filters = []): LengthAwarePaginator;
}
