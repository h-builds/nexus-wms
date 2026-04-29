<?php

declare(strict_types=1);

namespace App\Modules\Intelligence\Infrastructure\Http\Controllers;

use App\Modules\Intelligence\Application\Actions\AcknowledgeDecisionTraceAction;
use App\Modules\Intelligence\Application\Actions\ActUponDecisionTraceAction;
use App\Modules\Intelligence\Application\Actions\DismissDecisionTraceAction;
use App\Modules\Intelligence\Application\Actions\GetDecisionTraceByIdAction;
use App\Modules\Intelligence\Application\Actions\GetDecisionTraceMetricsAction;
use App\Modules\Intelligence\Application\Actions\ListDecisionTracesAction;
use App\Modules\Intelligence\Application\DTOs\DecisionTraceListCriteria;
use App\Modules\Intelligence\Application\DTOs\DecisionTraceSortOrder;
use App\Modules\Intelligence\Domain\Entities\DecisionTrace;
use App\Modules\Intelligence\Domain\Enums\AgentDomain;
use App\Modules\Intelligence\Domain\Enums\TraceSeverity;
use App\Modules\Intelligence\Domain\Enums\TraceStatus;
use App\Modules\Intelligence\Domain\Exceptions\InvalidStateTransitionException;
use App\Modules\Intelligence\Infrastructure\Http\Requests\ActUponDecisionTraceRequest;
use App\Modules\Intelligence\Infrastructure\Http\Requests\DismissDecisionTraceRequest;
use App\Modules\Intelligence\Infrastructure\Http\Requests\ListDecisionTracesRequest;
use App\Modules\Intelligence\Infrastructure\Http\Resources\DecisionTraceResource;
use Illuminate\Http\JsonResponse;

final class DecisionTraceController
{
    public function getDecisionTraceMetrics(GetDecisionTraceMetricsAction $getMetricsAction): JsonResponse
    {
        return response()->json([
            'metrics' => $getMetricsAction->execute()->toArray(),
        ]);
    }

    public function listDecisionTraces(ListDecisionTracesRequest $request, ListDecisionTracesAction $listTracesAction): JsonResponse
    {
        $listCriteria = $this->buildListCriteria($request);
        $decisionTracesPaginator = $listTracesAction->execute($listCriteria);

        return \App\Http\Responses\PaginatedResponse::make($decisionTracesPaginator, DecisionTraceResource::class);
    }

    public function viewDecisionTrace(string $decisionTraceId, GetDecisionTraceByIdAction $getTraceByIdAction): JsonResponse
    {
        $decisionTraceDetails = $getTraceByIdAction->execute($decisionTraceId);

        if (!$decisionTraceDetails) {
            return $this->buildTraceNotFoundResponse($decisionTraceId);
        }

        return $this->buildSuccessfulTraceResponse($decisionTraceDetails);
    }

    public function acknowledgeDecisionTrace(string $decisionTraceId, AcknowledgeDecisionTraceAction $acknowledgeAction): JsonResponse
    {
        try {
            $decisionTraceDetails = $acknowledgeAction->execute($decisionTraceId);

            if (!$decisionTraceDetails) {
                return $this->buildTraceNotFoundResponse($decisionTraceId);
            }

            return $this->buildSuccessfulTraceResponse($decisionTraceDetails);
        } catch (InvalidStateTransitionException $domainValidationException) {
            return $this->buildInvalidStateTransitionResponse($domainValidationException->getMessage());
        }
    }

    public function actUponDecisionTrace(ActUponDecisionTraceRequest $request, string $decisionTraceId, ActUponDecisionTraceAction $actUponAction): JsonResponse
    {
        $actorId = (string) $request->validated('actor_id');

        try {
            $decisionTraceDetails = $actUponAction->execute($decisionTraceId, $actorId);

            if (!$decisionTraceDetails) {
                return $this->buildTraceNotFoundResponse($decisionTraceId);
            }

            return $this->buildSuccessfulTraceResponse($decisionTraceDetails);
        } catch (InvalidStateTransitionException $domainValidationException) {
            return $this->buildInvalidStateTransitionResponse($domainValidationException->getMessage());
        }
    }

    public function dismissDecisionTrace(DismissDecisionTraceRequest $request, string $decisionTraceId, DismissDecisionTraceAction $dismissAction): JsonResponse
    {
        $actorId = (string) $request->validated('actor_id');

        try {
            $decisionTraceDetails = $dismissAction->execute($decisionTraceId, $actorId);

            if (!$decisionTraceDetails) {
                return $this->buildTraceNotFoundResponse($decisionTraceId);
            }

            return $this->buildSuccessfulTraceResponse($decisionTraceDetails);
        } catch (InvalidStateTransitionException $domainValidationException) {
            return $this->buildInvalidStateTransitionResponse($domainValidationException->getMessage());
        }
    }

    private function buildListCriteria(ListDecisionTracesRequest $request): DecisionTraceListCriteria
    {
        $validatedFilters = $request->validated();
        
        return new DecisionTraceListCriteria(
            page: (int) ($validatedFilters['page'] ?? 1),
            perPage: (int) ($validatedFilters['per_page'] ?? 50),
            status: isset($validatedFilters['status']) ? TraceStatus::tryFrom((string) $validatedFilters['status']) : null,
            severity: isset($validatedFilters['severity']) ? TraceSeverity::tryFrom((string) $validatedFilters['severity']) : null,
            agentDomain: isset($validatedFilters['agentDomain']) ? AgentDomain::tryFrom((string) $validatedFilters['agentDomain']) : null,
            sortOrder: DecisionTraceSortOrder::fromStringOrDefault((string) ($validatedFilters['sort'] ?? 'createdAt_desc')),
        );
    }

    private function buildSuccessfulTraceResponse(DecisionTrace $decisionTrace): JsonResponse
    {
        return response()->json([
            'data' => new DecisionTraceResource($decisionTrace),
        ]);
    }

    private function buildTraceNotFoundResponse(string $decisionTraceId): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => 'not_found',
                'message' => "DecisionTrace [{$decisionTraceId}] not found.",
            ]
        ], 404);
    }

    private function buildInvalidStateTransitionResponse(string $errorMessage): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => 'invalid_state_transition',
                'message' => $errorMessage,
            ]
        ], 422);
    }
}
