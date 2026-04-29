<?php

declare(strict_types=1);

namespace App\Modules\Intelligence\Infrastructure\Persistence\Repositories;

use App\Modules\Intelligence\Application\DTOs\DecisionTraceListCriteria;
use App\Modules\Intelligence\Application\DTOs\DecisionTraceMetrics;
use App\Modules\Intelligence\Application\DTOs\DecisionTraceSortOrder;
use App\Modules\Intelligence\Application\Queries\DecisionTraceQueryService;
use App\Modules\Intelligence\Domain\Entities\DecisionTrace;
use App\Modules\Intelligence\Domain\Enums\AgentDomain;
use App\Modules\Intelligence\Domain\Enums\TraceSeverity;
use App\Modules\Intelligence\Domain\Enums\TraceStatus;
use App\Modules\Intelligence\Domain\Enums\TraceType;
use App\Modules\Intelligence\Domain\Repositories\DecisionTraceRepository;
use App\Modules\Intelligence\Infrastructure\Persistence\Eloquent\DecisionTraceModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class EloquentDecisionTraceRepository implements DecisionTraceRepository, DecisionTraceQueryService
{
    public function save(DecisionTrace $trace): void
    {
        DecisionTraceModel::updateOrCreate(
            [
                'causation_id' => $trace->causationId(),
                'agent_id' => $trace->agentId(),
            ],
            [
                'id' => $trace->id(),
                'trace_type' => $trace->traceType()->value,
                'agent_domain' => $trace->agentDomain()->value,
                'detection' => $trace->detection(),
                'reasoning' => $trace->reasoning(),
                'suggestion' => $trace->suggestion(),
                'severity' => $trace->severity()->value,
                'correlation_id' => $trace->correlationId(),
                'trigger_event_ids' => $trace->triggerEventIds(),
                'status' => $trace->status()->value,
                'acted_upon_at' => $trace->actedUponAt(),
                'acted_upon_by' => $trace->actedUponBy(),
            ]
        );
    }

    public function findById(string $id): ?DecisionTrace
    {
        $decisionTraceModel = DecisionTraceModel::find($id);

        if (!$decisionTraceModel) {
            return null;
        }

        return $this->toEntity($decisionTraceModel);
    }

    public function paginate(DecisionTraceListCriteria $criteria): LengthAwarePaginator
    {
        $query = DecisionTraceModel::query();

        $this->applyFilters($query, $criteria);
        $this->applySortOrder($query, $criteria->sortOrder);

        $paginator = $query->paginate(perPage: $criteria->perPage, page: $criteria->page);

        $paginator->getCollection()->transform(
            fn (DecisionTraceModel $decisionTraceModel): DecisionTrace => $this->toEntity($decisionTraceModel)
        );

        return $paginator;
    }

    public function existsByCausationId(string $causationId, string $agentId): bool
    {
        return DecisionTraceModel::where('causation_id', $causationId)
            ->where('agent_id', $agentId)
            ->exists();
    }

    public function getMetrics(): DecisionTraceMetrics
    {
        $traceCountByStatus = $this->countByColumn('status');
        $traceCountBySeverity = $this->countByColumn('severity');
        $traceCountByDomain = $this->countByColumn('agent_domain');

        return new DecisionTraceMetrics(
            totalTraces: array_sum($traceCountByStatus),
            advisoryCount: $traceCountByStatus[TraceStatus::Advisory->value] ?? 0,
            acknowledgedCount: $traceCountByStatus[TraceStatus::Acknowledged->value] ?? 0,
            actedUponCount: $traceCountByStatus[TraceStatus::ActedUpon->value] ?? 0,
            dismissedCount: $traceCountByStatus[TraceStatus::Dismissed->value] ?? 0,
            criticalCount: $traceCountBySeverity[TraceSeverity::Critical->value] ?? 0,
            highCount: $traceCountBySeverity[TraceSeverity::High->value] ?? 0,
            mediumCount: $traceCountBySeverity[TraceSeverity::Medium->value] ?? 0,
            lowCount: $traceCountBySeverity[TraceSeverity::Low->value] ?? 0,
            inventoryDomainCount: $traceCountByDomain[AgentDomain::Inventory->value] ?? 0,
            incidentsDomainCount: $traceCountByDomain[AgentDomain::Incidents->value] ?? 0,
            movementsDomainCount: $traceCountByDomain[AgentDomain::Movements->value] ?? 0,
            monitoringDomainCount: $traceCountByDomain[AgentDomain::Monitoring->value] ?? 0,
        );
    }

    private function applyFilters(Builder $query, DecisionTraceListCriteria $criteria): void
    {
        if ($criteria->status !== null) {
            $query->where('status', $criteria->status->value);
        }

        if ($criteria->severity !== null) {
            $query->where('severity', $criteria->severity->value);
        }

        if ($criteria->agentDomain !== null) {
            $query->where('agent_domain', $criteria->agentDomain->value);
        }
    }

    private function applySortOrder(Builder $query, DecisionTraceSortOrder $sortOrder): void
    {
        $direction = $sortOrder === DecisionTraceSortOrder::CreatedAtAsc ? 'asc' : 'desc';
        $query->orderBy('created_at', $direction);
    }

    private const ALLOWED_AGGREGATE_COLUMNS = ['status', 'severity', 'agent_domain'];

    /**
     * @return array<string, int>
     */
    private function countByColumn(string $column): array
    {
        if (!in_array($column, self::ALLOWED_AGGREGATE_COLUMNS, true)) {
            throw new \InvalidArgumentException(
                "Column '{$column}' is not allowed for aggregation. Allowed: " . implode(', ', self::ALLOWED_AGGREGATE_COLUMNS)
            );
        }

        return DecisionTraceModel::toBase()
            ->selectRaw("{$column}, count(*) as count")
            ->groupBy($column)
            ->pluck('count', $column)
            ->toArray();
    }

    private function toEntity(DecisionTraceModel $decisionTraceModel): DecisionTrace
    {
        return new DecisionTrace(
            id: $decisionTraceModel->id,
            traceType: TraceType::from($decisionTraceModel->trace_type),
            agentId: $decisionTraceModel->agent_id,
            agentDomain: AgentDomain::from($decisionTraceModel->agent_domain),
            detection: $decisionTraceModel->detection,
            reasoning: $decisionTraceModel->reasoning,
            suggestion: $decisionTraceModel->suggestion,
            severity: TraceSeverity::from($decisionTraceModel->severity),
            causationId: $decisionTraceModel->causation_id,
            correlationId: $decisionTraceModel->correlation_id,
            triggerEventIds: $decisionTraceModel->trigger_event_ids ?? [],
            status: TraceStatus::from($decisionTraceModel->status),
            createdAt: $decisionTraceModel->created_at->toIso8601String(),
            updatedAt: $decisionTraceModel->updated_at?->toIso8601String(),
            actedUponAt: $decisionTraceModel->acted_upon_at?->toIso8601String(),
            actedUponBy: $decisionTraceModel->acted_upon_by,
        );
    }
}
