<?php

declare(strict_types=1);

namespace App\Modules\Locations\Domain\Repositories;

use App\Modules\Locations\Domain\Entities\Location;

interface LocationRepository
{
    /**
     * @throws \App\Modules\Locations\Domain\Exceptions\DuplicateLocationLabel
     */
    public function add(Location $location): Location;

    public function update(Location $location): void;

    public function findById(string $id): ?Location;

    /**
     * @throws \App\Modules\Locations\Domain\Exceptions\LocationNotFound
     */
    public function getById(string $id): Location;

    public function findByLabel(string $label): ?Location;
}
