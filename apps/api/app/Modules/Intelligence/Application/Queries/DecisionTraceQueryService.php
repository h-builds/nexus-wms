<?php

declare(strict_types=1);

namespace App\Modules\Intelligence\Application\Queries;

use App\Modules\Intelligence\Application\DTOs\DecisionTraceListCriteria;
use App\Modules\Intelligence\Application\DTOs\DecisionTraceMetrics;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface DecisionTraceQueryService
{
    public function paginate(DecisionTraceListCriteria $criteria): LengthAwarePaginator;

    public function getMetrics(): DecisionTraceMetrics;
}
