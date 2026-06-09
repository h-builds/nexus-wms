<?php

declare(strict_types=1);

namespace App\Modules\Intelligence\Application\Actions;

use App\Modules\Intelligence\Application\DTOs\DecisionTraceListCriteria;
use App\Modules\Intelligence\Application\Queries\DecisionTraceQueryService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListDecisionTracesAction
{
    public function __construct(
        private readonly DecisionTraceQueryService $queryService,
    ) {}

    public function execute(DecisionTraceListCriteria $criteria): LengthAwarePaginator
    {
        return $this->queryService->paginate($criteria);
    }
}
