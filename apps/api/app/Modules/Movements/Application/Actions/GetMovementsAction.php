<?php

declare(strict_types=1);

namespace App\Modules\Movements\Application\Actions;

use App\Modules\Movements\Domain\Repositories\MovementRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class GetMovementsAction
{
    public function __construct(
        private readonly MovementRepository $repository
    ) {}

    /**
     * @param array<string, mixed> $filters
     */
    public function execute(int $page = 1, int $perPage = 50, array $filters = []): LengthAwarePaginator
    {
        return $this->repository->paginate($page, $perPage, $filters);
    }
}
