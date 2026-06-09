<?php

declare(strict_types=1);

namespace App\Modules\Intelligence\Application\Actions;

use App\Modules\Intelligence\Domain\Entities\DecisionTrace;
use App\Modules\Intelligence\Domain\Exceptions\DecisionTraceNotFound;
use App\Modules\Intelligence\Domain\Repositories\DecisionTraceRepository;

final readonly class AcknowledgeDecisionTraceAction
{
    public function __construct(
        private DecisionTraceRepository $decisionTraceRepository,
    ) {
    }

    public function execute(string $decisionTraceId): DecisionTrace
    {
        $decisionTrace = $this->decisionTraceRepository->findById($decisionTraceId);

        if (!$decisionTrace) {
            throw DecisionTraceNotFound::withId($decisionTraceId);
        }

        $decisionTrace->acknowledge(now()->toIso8601String());
        $this->decisionTraceRepository->save($decisionTrace);

        return $decisionTrace;
    }
}
