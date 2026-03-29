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
