<?php

declare(strict_types=1);

namespace App\Modules\Locations\Application\Actions;

use App\Modules\Locations\Domain\Repositories\LocationRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListLocationsAction
{
    public function __construct(
        private readonly LocationRepository $locations,
    ) {
    }

    public function execute(int $page = 1, int $perPage = 50, array $filters = []): LengthAwarePaginator
    {
        return $this->locations->paginate($page, $perPage, $filters);
    }
}
