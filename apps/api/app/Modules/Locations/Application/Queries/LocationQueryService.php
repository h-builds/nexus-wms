<?php

declare(strict_types=1);

namespace App\Modules\Locations\Application\Queries;

use App\Modules\Locations\Application\DTOs\LocationListCriteria;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface LocationQueryService
{
    public function paginate(int $page, int $perPage, LocationListCriteria $criteria): LengthAwarePaginator;
}
