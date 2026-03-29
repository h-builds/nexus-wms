<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Actions;

use App\Modules\Inventory\Domain\Entities\StockItem;
use App\Modules\Inventory\Domain\Exceptions\StockItemNotFound;
use App\Modules\Inventory\Domain\Repositories\StockItemRepository;

final class GetStockItemByIdAction
{
    public function __construct(
        private readonly StockItemRepository $stockItems,
    ) {
    }

    public function execute(string $id): StockItem
    {
        $stockItem = $this->stockItems->findById($id);

        if ($stockItem === null) {
            throw StockItemNotFound::withId($id);
        }

        return $stockItem;
    }
}
