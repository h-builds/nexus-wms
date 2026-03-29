<?php

declare(strict_types=1);

namespace App\Modules\Locations\Domain\Repositories;

use App\Modules\Locations\Domain\Entities\Location;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface LocationRepository
{
    public function create(Location $location): Location;

    public function findById(string $id): ?Location;

    public function findByLabel(string $label): ?Location;

    /**
     * @return LengthAwarePaginator
     */
    public function paginate(int $page = 1, int $perPage = 50, array $filters = []): LengthAwarePaginator;
}
