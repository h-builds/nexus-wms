<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Infrastructure\Persistence\Eloquent;

use App\Modules\Inventory\Domain\Entities\StockItem;
use App\Modules\Inventory\Domain\Enums\InventoryStatus;
use App\Modules\Inventory\Domain\Repositories\StockItemRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class EloquentStockItemRepository implements StockItemRepository
{
    public function findById(string $id): ?StockItem
    {
        $model = StockItemModel::query()->find($id);

        return $model ? $this->toDomain($model) : null;
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function paginate(int $page = 1, int $perPage = 50, array $filters = []): LengthAwarePaginator
    {
        $query = StockItemModel::query();

        $this->applyFilters($query, $filters);

        $paginator = $query
            ->orderBy('created_at', 'desc')
            ->paginate(perPage: $perPage, page: $page);

        $paginator->getCollection()->transform(
            fn (StockItemModel $model): StockItem => $this->toDomain($model)
        );

        return $paginator;
    }

    public function findByProductAndLocation(string $productId, string $locationId, ?string $lotNumber = null): ?StockItem
    {
        $query = StockItemModel::query()
            ->where('product_id', $productId)
            ->where('location_id', $locationId);

        if ($lotNumber === null) {
            $query->whereNull('lot_number');
        } else {
            $query->where('lot_number', $lotNumber);
        }

        $model = $query->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function updateQuantity(string $stockItemId, int $newAvailable, int $newOnHand, int $expectedVersion): bool
    {
        $updatedRowsCount = StockItemModel::query()
            ->where('id', $stockItemId)
            ->where('version', $expectedVersion)
            ->update([
                'quantity_available' => $newAvailable,
                'quantity_on_hand' => $newOnHand,
                'version' => $expectedVersion + 1,
                'updated_at' => now(),
            ]);

        return $updatedRowsCount > 0;
    }

    public function insertStockItem(StockItem $stockItem): void
    {
        StockItemModel::query()->insert([
            'id' => $stockItem->id(),
            'product_id' => $stockItem->productId(),
            'location_id' => $stockItem->locationId(),
            'quantity_available' => $stockItem->quantityAvailable(),
            'quantity_on_hand' => $stockItem->quantityOnHand(),
            'quantity_blocked' => $stockItem->quantityBlocked(),
            'lot_number' => $stockItem->lotNumber(),
            'serial_number' => $stockItem->serialNumber(),
            'received_at' => $stockItem->receivedAt(),
            'expires_at' => $stockItem->expiresAt(),
            'status' => $stockItem->status()->value,
            'version' => $stockItem->version(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if (isset($filters['productId']) && $filters['productId'] !== '') {
            $query->where('product_id', $filters['productId']);
        }

        if (isset($filters['locationId']) && $filters['locationId'] !== '') {
            $query->where('location_id', $filters['locationId']);
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }
    }

    private function toDomain(StockItemModel $model): StockItem
    {
        return new StockItem(
            id: (string) $model->getAttribute('id'),
            productId: (string) $model->getAttribute('product_id'),
            locationId: (string) $model->getAttribute('location_id'),
            quantityOnHand: (int) $model->getAttribute('quantity_on_hand'),
            quantityAvailable: (int) $model->getAttribute('quantity_available'),
            quantityBlocked: (int) $model->getAttribute('quantity_blocked'),
            lotNumber: $model->getAttribute('lot_number'),
            serialNumber: $model->getAttribute('serial_number'),
            receivedAt: $model->getAttribute('received_at')?->toIso8601String(),
            expiresAt: $model->getAttribute('expires_at')?->toIso8601String(),
            status: InventoryStatus::from((string) $model->getAttribute('status')),
            version: (int) $model->getAttribute('version'),
            updatedAt: $model->getAttribute('updated_at')?->toIso8601String(),
        );
    }
}
