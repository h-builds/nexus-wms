<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Services;

use App\Modules\Inventory\Domain\Entities\StockItem;
use App\Modules\Inventory\Domain\Enums\InventoryStatus;
use App\Modules\Inventory\Domain\Exceptions\OptimisticLockException;
use App\Modules\Inventory\Domain\Exceptions\StockItemNotFound;
use App\Modules\Inventory\Domain\Repositories\StockItemRepository;
use App\Modules\Inventory\Domain\Services\InventoryValidator;
use App\Modules\Inventory\Application\DTOs\StockMutationDTO;
use App\Modules\Inventory\Domain\Enums\MutationOperation;
use Illuminate\Support\Str;

/**
 * Service explicitly and exclusively for mutating stock levels.
 * This is an internal module API, called by Movements domain.
 */
class InternalStockMutationService
{
    public function __construct(
        private readonly InventoryValidator $validator,
        private readonly StockItemRepository $repository
    ) {}

    public function applyMutation(StockMutationDTO $mutation): void
    {
        match ($mutation->operation) {
            MutationOperation::ADD => $this->addStock($mutation->toLocationId, $mutation->productId, $mutation->quantity, $mutation->lotNumber),
            MutationOperation::SUBTRACT => $this->subtractStock($mutation->fromLocationId, $mutation->productId, $mutation->quantity, $mutation->lotNumber),
            MutationOperation::MOVE => $this->moveStock($mutation->fromLocationId, $mutation->toLocationId, $mutation->productId, $mutation->quantity, $mutation->lotNumber),
        };
    }

    public function getQuantityOnHand(string $productId, string $locationId, ?string $lotNumber = null): int
    {
        $stockItem = $this->repository->findByProductAndLocation($productId, $locationId, $lotNumber);

        return $stockItem ? $stockItem->quantityOnHand() : 0;
    }

    private function moveStock(string $fromLocationId, string $toLocationId, string $productId, int $quantity, ?string $lotNumber): void
    {
        $this->subtractStock($fromLocationId, $productId, $quantity, $lotNumber);
        $this->addStock($toLocationId, $productId, $quantity, $lotNumber);
    }

    private function addStock(string $locationId, string $productId, int $quantity, ?string $lotNumber): void
    {
        $stockItem = $this->repository->findByProductAndLocation($productId, $locationId, $lotNumber);

        if ($stockItem) {
            $this->updateExistingStock($stockItem, $quantity);
        } else {
            $this->insertNewStockItem($locationId, $productId, $quantity, $lotNumber);
        }
    }

    private function subtractStock(string $locationId, string $productId, int $quantity, ?string $lotNumber): void
    {
        $stockItem = $this->repository->findByProductAndLocation($productId, $locationId, $lotNumber);

        if (!$stockItem) {
            throw StockItemNotFound::forProductAndLocation($productId, $locationId);
        }

        $this->updateExistingStock($stockItem, -$quantity);
    }

    private function updateExistingStock(StockItem $stockItem, int $quantityDelta): void
    {
        $this->validator->validateAdjustment(
            currentAvailable: $stockItem->quantityAvailable(),
            currentBlocked: $stockItem->quantityBlocked(),
            deltaAvailable: $quantityDelta,
            deltaBlocked: 0
        );

        $newAvailable = $stockItem->quantityAvailable() + $quantityDelta;
        $newOnHand = $this->validator->computeOnHand($newAvailable, $stockItem->quantityBlocked());

        $updated = $this->repository->updateQuantity(
            $stockItem->id(),
            $newAvailable,
            $newOnHand,
            $stockItem->version()
        );

        if (!$updated) {
            throw OptimisticLockException::forStockItem($stockItem->id());
        }
    }

    private function insertNewStockItem(string $locationId, string $productId, int $quantity, ?string $lotNumber): void
    {
        $newAvailable = $quantity;
        $newOnHand = $this->validator->computeOnHand($newAvailable, 0);

        $stockItem = new StockItem(
            id: Str::uuid()->toString(),
            productId: $productId,
            locationId: $locationId,
            quantityOnHand: $newOnHand,
            quantityAvailable: $newAvailable,
            quantityBlocked: 0,
            lotNumber: $lotNumber,
            serialNumber: null,
            receivedAt: null,
            expiresAt: null,
            status: InventoryStatus::AVAILABLE,
            version: 1,
            updatedAt: now()->toIso8601String(),
        );

        $this->repository->insertStockItem($stockItem);
    }
}
