<?php

declare(strict_types=1);

namespace App\Modules\Movements\Application\Actions;

use App\Modules\Movements\Domain\Entities\InventoryMovement;
use App\Modules\Movements\Domain\Repositories\MovementRepository;

final class GetMovementByIdAction
{
    public function __construct(
        private readonly MovementRepository $repository
    ) {}

    public function execute(string $id): ?InventoryMovement
    {
        return $this->repository->findById($id);
    }
}
