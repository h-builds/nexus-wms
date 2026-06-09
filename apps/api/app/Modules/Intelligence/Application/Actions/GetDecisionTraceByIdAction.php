<?php

declare(strict_types=1);

namespace App\Modules\Intelligence\Application\Actions;

use App\Modules\Intelligence\Domain\Entities\DecisionTrace;
use App\Modules\Intelligence\Domain\Exceptions\DecisionTraceNotFound;
use App\Modules\Intelligence\Domain\Repositories\DecisionTraceRepository;

final readonly class GetDecisionTraceByIdAction
{
    public function __construct(
        private DecisionTraceRepository $repository,
    ) {}

    public function execute(string $id): DecisionTrace
    {
        $trace = $this->repository->findById($id);

        if ($trace === null) {
            throw DecisionTraceNotFound::withId($id);
        }

        return $trace;
    }
}
