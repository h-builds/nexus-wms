<?php

declare(strict_types=1);

namespace App\Modules\Incidents\Domain\Repositories;

use App\Modules\Incidents\Domain\Entities\InventoryIncident;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface IncidentRepository
{
    public function save(InventoryIncident $incident): void;

    public function findById(string $id): ?InventoryIncident;

    public function findByIdempotencyKey(string $key): ?InventoryIncident;

    public function paginate(int $page = 1, int $perPage = 50, array $filters = []): LengthAwarePaginator;
}
