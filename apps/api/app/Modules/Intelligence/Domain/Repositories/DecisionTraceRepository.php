<?php

declare(strict_types=1);

namespace App\Modules\Intelligence\Domain\Repositories;

use App\Modules\Intelligence\Domain\Entities\DecisionTrace;

interface DecisionTraceRepository
{
    public function save(DecisionTrace $trace): void;

    public function findById(string $id): ?DecisionTrace;

    public function existsByCausationId(string $causationId, string $agentId): bool;
}
