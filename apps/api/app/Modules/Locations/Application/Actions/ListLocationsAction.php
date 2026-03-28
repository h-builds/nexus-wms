<?php

declare(strict_types=1);

namespace App\Modules\Locations\Application\Actions;

use App\Modules\Locations\Domain\Repositories\LocationRepository;

final class ListLocationsAction
{
    public function __construct(
        private readonly LocationRepository $locations,
    ) {
    }

    public function execute(): array
    {
        return $this->locations->all();
    }
}
