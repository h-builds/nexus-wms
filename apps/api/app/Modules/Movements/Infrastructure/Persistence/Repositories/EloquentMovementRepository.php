<?php

declare(strict_types=1);

namespace App\Modules\Movements\Infrastructure\Persistence\Repositories;

use App\Modules\Movements\Domain\Entities\InventoryMovement;
use App\Modules\Movements\Domain\Enums\MovementType;
use App\Modules\Movements\Domain\Repositories\MovementRepository;
use App\Modules\Movements\Infrastructure\Persistence\Eloquent\InventoryMovementModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentMovementRepository implements MovementRepository
{
    public function save(InventoryMovement $movement): void
    {
        InventoryMovementModel::updateOrCreate(
            ['id' => $movement->id()],
            [
                'product_id' => $movement->productId(),
                'from_location_id' => $movement->fromLocationId(),
                'to_location_id' => $movement->toLocationId(),
                'type' => $movement->type()->value,
                'quantity' => $movement->quantity(),
                'reference' => $movement->reference(),
                'lot_number' => $movement->lotNumber(),
                'reason' => $movement->reason(),
                'performed_by' => $movement->performedBy(),
                'performed_at' => $movement->performedAt(),
                'idempotency_key' => $movement->idempotencyKey(),
                'created_at' => $movement->createdAt(),
                'updated_at' => $movement->updatedAt(),
            ]
        );
    }

    public function findById(string $id): ?InventoryMovement
    {
        $model = InventoryMovementModel::find($id);

        if (!$model) {
            return null;
        }

        return $this->toEntity($model);
    }

    public function findByIdempotencyKey(string $key): ?InventoryMovement
    {
        $model = InventoryMovementModel::where('idempotency_key', $key)->first();

        if (!$model) {
            return null;
        }

        return $this->toEntity($model);
    }

    public function paginate(int $page = 1, int $perPage = 50, array $filters = []): LengthAwarePaginator
    {
        $query = InventoryMovementModel::query();

        if (array_key_exists('productId', $filters) && $filters['productId'] !== null) {
            $query->where('product_id', $filters['productId']);
        }

        if (array_key_exists('type', $filters) && $filters['type'] !== null) {
            $query->where('type', $filters['type']);
        }

        if (array_key_exists('fromLocationId', $filters) && $filters['fromLocationId'] !== null) {
            $query->where('from_location_id', $filters['fromLocationId']);
        }

        if (array_key_exists('toLocationId', $filters) && $filters['toLocationId'] !== null) {
            $query->where('to_location_id', $filters['toLocationId']);
        }

        $query->orderBy('performed_at', 'desc');

        $paginator = $query->paginate(perPage: $perPage, page: $page);

        $paginator->getCollection()->transform(function (InventoryMovementModel $model) {
            return $this->toEntity($model);
        });

        return $paginator;
    }

    private function toEntity(InventoryMovementModel $model): InventoryMovement
    {
        return new InventoryMovement(
            id: $model->id,
            productId: $model->product_id,
            fromLocationId: $model->from_location_id,
            toLocationId: $model->to_location_id,
            type: MovementType::from($model->type),
            quantity: $model->quantity,
            reference: $model->reference,
            lotNumber: $model->lot_number,
            reason: $model->reason,
            performedBy: $model->performed_by,
            performedAt: $model->performed_at->toIso8601String(),
            idempotencyKey: $model->idempotency_key,
            createdAt: $model->created_at?->toIso8601String(),
            updatedAt: $model->updated_at?->toIso8601String(),
        );
    }
}
