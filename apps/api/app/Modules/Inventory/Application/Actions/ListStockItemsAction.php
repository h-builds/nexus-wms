<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Actions;

use App\Modules\Inventory\Domain\Repositories\StockItemRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListStockItemsAction
{
    public function __construct(
        private readonly StockItemRepository $stockItems,
    ) {
    }

    /**
     * @param array<string, mixed> $filters Supported: productId, locationId, status
     */
    public function execute(int $page = 1, int $perPage = 50, array $filters = []): LengthAwarePaginator
    {
        $perPage = min($perPage, 100);

        return $this->stockItems->paginate($page, $perPage, $filters);
    }
}
