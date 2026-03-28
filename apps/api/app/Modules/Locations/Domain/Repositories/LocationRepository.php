<?php

declare(strict_types=1);

namespace App\Modules\Locations\Domain\Repositories;

use App\Modules\Locations\Domain\Entities\Location;

interface LocationRepository
{
    public function create(Location $location): Location;

    public function findById(string $id): ?Location;

    public function findByLabel(string $label): ?Location;

    /**
     * @return Location[]
     */
    public function all(): array;
}
