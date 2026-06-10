<?php

declare(strict_types=1);

namespace App\Modules\Locations\Infrastructure\Persistence\Eloquent;

use App\Modules\Locations\Application\DTOs\LocationListCriteria;
use App\Modules\Locations\Application\Queries\LocationQueryService;
use App\Modules\Locations\Domain\Entities\Location;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentLocationQueryService implements LocationQueryService
{
    public function paginate(int $page = 1, int $perPage = 50, ?LocationListCriteria $criteria = null): LengthAwarePaginator
    {
        $locationQuery = LocationModel::query();

        if ($criteria !== null) {
            if ($criteria->warehouseCode !== null) {
                $locationQuery->where('warehouse_code', $criteria->warehouseCode);
            }
            if ($criteria->zone !== null) {
                $locationQuery->where('zone', $criteria->zone);
            }
            if ($criteria->aisle !== null) {
                $locationQuery->where('aisle', $criteria->aisle);
            }
            if ($criteria->rack !== null) {
                $locationQuery->where('rack', $criteria->rack);
            }
            if ($criteria->bin !== null) {
                $locationQuery->where('bin', $criteria->bin);
            }
        }

        $paginator = $locationQuery
            ->orderBy('label')
            ->paginate(perPage: $perPage, page: $page);

        $paginator->getCollection()->transform(fn (LocationModel $locationModel): Location => $this->toDomain($locationModel));

        return $paginator;
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
