<?php

declare(strict_types=1);

namespace App\Modules\Locations\Application\Actions;

use App\Modules\Locations\Application\DTOs\LocationListCriteria;
use App\Modules\Locations\Application\Queries\LocationQueryService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListLocationsAction
{
    public function __construct(
        private readonly LocationQueryService $queries,
    ) {
    }

    public function execute(int $page = 1, int $perPage = 50, ?LocationListCriteria $criteria = null): LengthAwarePaginator
    {
        return $this->queries->paginate($page, $perPage, $criteria ?? new LocationListCriteria());
    }
}
