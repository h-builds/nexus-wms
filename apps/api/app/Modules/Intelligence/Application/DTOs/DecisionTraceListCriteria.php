<?php

declare(strict_types=1);

namespace App\Modules\Intelligence\Application\DTOs;

use App\Modules\Intelligence\Domain\Enums\AgentDomain;
use App\Modules\Intelligence\Domain\Enums\TraceSeverity;
use App\Modules\Intelligence\Domain\Enums\TraceStatus;

final readonly class DecisionTraceListCriteria
{
    public function __construct(
        public int $page = 1,
        public int $perPage = 50,
        public ?TraceStatus $status = null,
        public ?TraceSeverity $severity = null,
        public ?AgentDomain $agentDomain = null,
        public DecisionTraceSortOrder $sortOrder = DecisionTraceSortOrder::CreatedAtDesc,
    ) {}
}
