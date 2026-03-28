<?php

declare(strict_types=1);

namespace App\Modules\Locations\Infrastructure\Persistence\Eloquent;

use App\Modules\Locations\Domain\Entities\Location;
use App\Modules\Locations\Domain\Repositories\LocationRepository;

final class EloquentLocationRepository implements LocationRepository
{
    public function create(Location $location): Location
    {
        LocationModel::query()->create([
            'id' => $location->id(),
            'warehouse_code' => $location->warehouseCode(),
            'zone' => $location->zone(),
            'aisle' => $location->aisle(),
            'rack' => $location->rack(),
            'level' => $location->level(),
            'bin' => $location->bin(),
            'label' => $location->label(),
            'is_blocked' => $location->isBlocked(),
        ]);

        return $location;
    }

    public function findById(string $id): ?Location
    {
        $model = LocationModel::query()->find($id);

        return $model ? $this->toDomain($model) : null;
    }

    public function findByLabel(string $label): ?Location
    {
        $model = LocationModel::query()
            ->where('label', $label)
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function all(): array
    {
        return LocationModel::query()
            ->orderBy('label')
            ->get()
            ->map(fn (LocationModel $model): Location => $this->toDomain($model))
            ->all();
    }

    private function toDomain(LocationModel $model): Location
    {
        return new Location(
            id: (string) $model->getAttribute('id'),
            warehouseCode: (string) $model->getAttribute('warehouse_code'),
            zone: (string) $model->getAttribute('zone'),
            aisle: (string) $model->getAttribute('aisle'),
            rack: (string) $model->getAttribute('rack'),
            level: (string) $model->getAttribute('level'),
            bin: (string) $model->getAttribute('bin'),
            label: (string) $model->getAttribute('label'),
            isBlocked: (bool) $model->getAttribute('is_blocked'),
        );
    }
}
