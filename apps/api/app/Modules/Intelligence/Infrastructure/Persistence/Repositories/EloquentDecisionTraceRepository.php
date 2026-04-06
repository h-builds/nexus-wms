<?php

declare(strict_types=1);

namespace App\Modules\Intelligence\Infrastructure\Persistence\Repositories;

use App\Modules\Intelligence\Application\DTOs\DecisionTraceMetrics;
use App\Modules\Intelligence\Domain\Entities\DecisionTrace;
use App\Modules\Intelligence\Domain\Enums\AgentDomain;
use App\Modules\Intelligence\Domain\Enums\TraceSeverity;
use App\Modules\Intelligence\Domain\Enums\TraceStatus;
use App\Modules\Intelligence\Domain\Enums\TraceType;
use App\Modules\Intelligence\Domain\Repositories\DecisionTraceRepository;
use App\Modules\Intelligence\Infrastructure\Persistence\Eloquent\DecisionTraceModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class EloquentDecisionTraceRepository implements DecisionTraceRepository
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
        $model = DecisionTraceModel::find($id);

        if (!$model) {
            return null;
        }

        return $this->toEntity($model);
    }

    public function paginate(int $page = 1, int $perPage = 50, array $filters = [], string $sort = 'createdAt_desc'): LengthAwarePaginator
    {
        $query = DecisionTraceModel::query();

        $this->applyFilters($query, $filters);
        $this->applySortOrder($query, $sort);

        $paginator = $query->paginate(perPage: $perPage, page: $page);

        $paginator->getCollection()->transform(
            fn (DecisionTraceModel $model): DecisionTrace => $this->toEntity($model)
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
            advisoryCount: $traceCountByStatus['advisory'] ?? 0,
            acknowledgedCount: $traceCountByStatus['acknowledged'] ?? 0,
            actedUponCount: $traceCountByStatus['acted_upon'] ?? 0,
            dismissedCount: $traceCountByStatus['dismissed'] ?? 0,
            criticalCount: $traceCountBySeverity['critical'] ?? 0,
            highCount: $traceCountBySeverity['high'] ?? 0,
            mediumCount: $traceCountBySeverity['medium'] ?? 0,
            lowCount: $traceCountBySeverity['low'] ?? 0,
            inventoryDomainCount: $traceCountByDomain['inventory'] ?? 0,
            incidentsDomainCount: $traceCountByDomain['incidents'] ?? 0,
            movementsDomainCount: $traceCountByDomain['movements'] ?? 0,
            monitoringDomainCount: $traceCountByDomain['monitoring'] ?? 0,
        );
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }

        if (isset($filters['agentDomain'])) {
            $query->where('agent_domain', $filters['agentDomain']);
        }
    }

    private function applySortOrder(Builder $query, string $sort): void
    {
        $direction = $sort === 'createdAt_asc' ? 'asc' : 'desc';
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

    private function toEntity(DecisionTraceModel $model): DecisionTrace
    {
        return new DecisionTrace(
            id: $model->id,
            traceType: TraceType::from($model->trace_type),
            agentId: $model->agent_id,
            agentDomain: AgentDomain::from($model->agent_domain),
            detection: $model->detection,
            reasoning: $model->reasoning,
            suggestion: $model->suggestion,
            severity: TraceSeverity::from($model->severity),
            causationId: $model->causation_id,
            correlationId: $model->correlation_id,
            triggerEventIds: $model->trigger_event_ids ?? [],
            status: TraceStatus::from($model->status),
            createdAt: $model->created_at->toIso8601String(),
            updatedAt: $model->updated_at?->toIso8601String(),
            actedUponAt: $model->acted_upon_at?->toIso8601String(),
            actedUponBy: $model->acted_upon_by,
        );
    }
}
