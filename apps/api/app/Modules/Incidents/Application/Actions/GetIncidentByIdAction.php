<?php

declare(strict_types=1);

namespace App\Modules\Incidents\Application\Actions;

use App\Modules\Incidents\Domain\Entities\InventoryIncident;
use App\Modules\Incidents\Domain\Repositories\IncidentRepository;

final class GetIncidentByIdAction
{
    public function __construct(
        private readonly IncidentRepository $repository
    ) {}

    public function execute(string $id): ?InventoryIncident
    {
        return $this->repository->findById($id);
    }
}
