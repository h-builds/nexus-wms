<?php

declare(strict_types=1);

namespace App\Modules\Incidents\Application\Actions;

use App\Modules\Incidents\Domain\Repositories\IncidentRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class GetIncidentsAction
{
    public function __construct(
        private readonly IncidentRepository $repository
    ) {}

    public function execute(int $page = 1, int $perPage = 50, array $filters = []): LengthAwarePaginator
    {
        return $this->repository->paginate($page, $perPage, $filters);
    }
}
