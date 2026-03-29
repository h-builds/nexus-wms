<?php

declare(strict_types=1);

namespace App\Modules\Incidents\Infrastructure\Persistence\Repositories;

use App\Modules\Incidents\Domain\Entities\InventoryIncident;
use App\Modules\Incidents\Domain\Enums\IncidentSeverity;
use App\Modules\Incidents\Domain\Enums\IncidentStatus;
use App\Modules\Incidents\Domain\Enums\IncidentType;
use App\Modules\Incidents\Domain\Repositories\IncidentRepository;
use App\Modules\Incidents\Infrastructure\Persistence\Eloquent\InventoryIncidentModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentIncidentRepository implements IncidentRepository
{
    public function save(InventoryIncident $incident): void
    {
        InventoryIncidentModel::updateOrCreate(
            ['id' => $incident->id()],
            [
                'product_id' => $incident->productId(),
                'location_id' => $incident->locationId(),
                'type' => $incident->type()->value,
                'severity' => $incident->severity()->value,
                'status' => $incident->status()->value,
                'description' => $incident->description(),
                'quantity_affected' => $incident->quantityAffected(),
                'reported_by' => $incident->reportedBy(),
                'assigned_to' => $incident->assignedTo(),
                'notes' => $incident->notes(),
                'created_at' => $incident->createdAt(),
                'updated_at' => $incident->updatedAt(),
                'idempotency_key' => $incident->idempotencyKey(),
            ]
        );
    }

    public function findById(string $id): ?InventoryIncident
    {
        $model = InventoryIncidentModel::find($id);

        if (!$model) {
            return null;
        }

        return $this->toEntity($model);
    }

    public function findByIdempotencyKey(string $key): ?InventoryIncident
    {
        $model = InventoryIncidentModel::where('idempotency_key', $key)->first();

        if (!$model) {
            return null;
        }

        return $this->toEntity($model);
    }

    public function paginate(int $page = 1, int $perPage = 50, array $filters = []): LengthAwarePaginator
    {
        $query = InventoryIncidentModel::query();

        if (array_key_exists('productId', $filters) && $filters['productId'] !== null) {
            $query->where('product_id', $filters['productId']);
        }

        if (array_key_exists('locationId', $filters) && $filters['locationId'] !== null) {
            $query->where('location_id', $filters['locationId']);
        }

        if (array_key_exists('type', $filters) && $filters['type'] !== null) {
            $query->where('type', $filters['type']);
        }

        if (array_key_exists('status', $filters) && $filters['status'] !== null) {
            $query->where('status', $filters['status']);
        }

        $query->orderBy('created_at', 'desc');

        $paginator = $query->paginate(perPage: $perPage, page: $page);

        $paginator->getCollection()->transform(function (InventoryIncidentModel $model) {
            return $this->toEntity($model);
        });

        return $paginator;
    }

    private function toEntity(InventoryIncidentModel $model): InventoryIncident
    {
        return new InventoryIncident(
            id: $model->id,
            productId: $model->product_id,
            locationId: $model->location_id,
            type: IncidentType::from($model->type),
            severity: IncidentSeverity::from($model->severity),
            status: IncidentStatus::from($model->status),
            description: $model->description,
            quantityAffected: $model->quantity_affected,
            reportedBy: $model->reported_by,
            createdAt: $model->created_at->toIso8601String(),
            updatedAt: $model->updated_at->toIso8601String(),
            idempotencyKey: $model->idempotency_key,
            assignedTo: $model->assigned_to,
            notes: $model->notes,
        );
    }
}
