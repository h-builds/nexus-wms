<?php

declare(strict_types=1);

namespace App\Modules\Movements\Domain\Repositories;

use App\Modules\Movements\Domain\Entities\InventoryMovement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface MovementRepository
{
    public function save(InventoryMovement $movement): void;

    public function findById(string $id): ?InventoryMovement;

    public function findByIdempotencyKey(string $key): ?InventoryMovement;

    /**
     * @param array<string, mixed> $filters
     */
    public function paginate(int $page = 1, int $perPage = 50, array $filters = []): LengthAwarePaginator;
}
