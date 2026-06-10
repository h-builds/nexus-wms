<?php

declare(strict_types=1);

namespace App\Modules\Locations\Infrastructure\Persistence\Eloquent;

use App\Modules\Locations\Domain\Entities\Location;
use App\Modules\Locations\Domain\Exceptions\DuplicateLocationLabel;
use App\Modules\Locations\Domain\Exceptions\LocationNotFound;
use App\Modules\Locations\Domain\Repositories\LocationRepository;
use Illuminate\Database\QueryException;

final class EloquentLocationRepository implements LocationRepository
{
    public function add(Location $location): Location
    {
        try {
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
        } catch (QueryException $exception) {
            if ($exception->getCode() === '23000') {
                throw DuplicateLocationLabel::withLabel($location->label());
            }
            throw $exception;
        }

        return $location;
    }

    public function update(Location $location): void
    {
        LocationModel::query()
            ->where('id', $location->id())
            ->update([
                'warehouse_code' => $location->warehouseCode(),
                'zone' => $location->zone(),
                'aisle' => $location->aisle(),
                'rack' => $location->rack(),
                'level' => $location->level(),
                'bin' => $location->bin(),
                'label' => $location->label(),
                'is_blocked' => $location->isBlocked(),
                'updated_at' => now(),
            ]);
    }

    public function findById(string $id): ?Location
    {
        $locationModel = LocationModel::query()->find($id);

        return $locationModel ? $this->toDomain($locationModel) : null;
    }

    public function getById(string $id): Location
    {
        $location = $this->findById($id);

        if (!$location) {
            throw LocationNotFound::withId($id);
        }

        return $location;
    }

    public function findByLabel(string $label): ?Location
    {
        $locationModel = LocationModel::query()
            ->where('label', $label)
            ->first();

        return $locationModel ? $this->toDomain($locationModel) : null;
    }



    private function toDomain(LocationModel $locationModel): Location
    {
        return new Location(
            id: (string) $locationModel->getAttribute('id'),
            warehouseCode: (string) $locationModel->getAttribute('warehouse_code'),
            zone: (string) $locationModel->getAttribute('zone'),
            aisle: (string) $locationModel->getAttribute('aisle'),
            rack: (string) $locationModel->getAttribute('rack'),
            level: (string) $locationModel->getAttribute('level'),
            bin: (string) $locationModel->getAttribute('bin'),
            label: (string) $locationModel->getAttribute('label'),
            isBlocked: (bool) $locationModel->getAttribute('is_blocked'),
        );
    }
}
