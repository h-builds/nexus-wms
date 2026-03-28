<?php

declare(strict_types=1);

namespace App\Modules\Locations\Application\Actions;

use App\Modules\Locations\Domain\Entities\Location;
use App\Modules\Locations\Domain\Exceptions\LocationNotFound;
use App\Modules\Locations\Domain\Repositories\LocationRepository;

final class GetLocationByIdAction
{
    public function __construct(
        private readonly LocationRepository $locations,
    ) {
    }

    public function execute(string $id): Location
    {
        $location = $this->locations->findById($id);

        if ($location === null) {
            throw LocationNotFound::withId($id);
        }

        return $location;
    }
}
