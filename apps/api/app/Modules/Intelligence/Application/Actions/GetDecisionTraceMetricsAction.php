<?php

declare(strict_types=1);

namespace App\Modules\Intelligence\Application\Actions;

use App\Modules\Intelligence\Application\DTOs\DecisionTraceMetrics;
use App\Modules\Intelligence\Application\Queries\DecisionTraceQueryService;

final readonly class GetDecisionTraceMetricsAction
{
    public function __construct(
        private DecisionTraceQueryService $queryService,
    ) {}

    public function execute(): DecisionTraceMetrics
    {
        return $this->queryService->getMetrics();
    }
}
