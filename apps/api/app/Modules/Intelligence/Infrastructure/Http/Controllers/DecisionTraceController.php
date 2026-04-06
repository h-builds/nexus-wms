<?php

declare(strict_types=1);

namespace App\Modules\Intelligence\Infrastructure\Http\Controllers;

use App\Modules\Intelligence\Application\Actions\AcknowledgeDecisionTraceAction;
use App\Modules\Intelligence\Application\Actions\ActUponDecisionTraceAction;
use App\Modules\Intelligence\Application\Actions\DismissDecisionTraceAction;
use App\Modules\Intelligence\Application\Actions\GetDecisionTraceByIdAction;
use App\Modules\Intelligence\Application\Actions\GetDecisionTraceMetricsAction;
use App\Modules\Intelligence\Application\Actions\ListDecisionTracesAction;
use App\Modules\Intelligence\Domain\Entities\DecisionTrace;
use App\Modules\Intelligence\Infrastructure\Http\Requests\ActUponDecisionTraceRequest;
use App\Modules\Intelligence\Infrastructure\Http\Requests\DismissDecisionTraceRequest;
use App\Modules\Intelligence\Infrastructure\Http\Requests\ListDecisionTracesRequest;
use App\Modules\Intelligence\Infrastructure\Http\Resources\DecisionTraceResource;
use Illuminate\Http\JsonResponse;

final class DecisionTraceController
{
    public function metrics(GetDecisionTraceMetricsAction $getMetricsAction): JsonResponse
    {
        return response()->json([
            'data' => $getMetricsAction->execute()->toArray(),
        ]);
    }

    public function index(ListDecisionTracesRequest $request, ListDecisionTracesAction $listTracesAction): JsonResponse
    {
        $validatedData = $request->validated();

        $pageNumber = (int) ($validatedData['page'] ?? 1);
        $itemsPerPage = (int) ($validatedData['per_page'] ?? 50);

        $activeFilters = array_filter([
            'status' => $validatedData['status'] ?? null,
            'severity' => $validatedData['severity'] ?? null,
            'agentDomain' => $validatedData['agentDomain'] ?? null,
        ]);
        
        $sortCriteria = (string) ($validatedData['sort'] ?? 'createdAt_desc');

        $paginatedResults = $listTracesAction->execute($pageNumber, $itemsPerPage, $activeFilters, $sortCriteria);

        return \App\Http\Responses\PaginatedResponse::make($paginatedResults, DecisionTraceResource::class);
    }

    public function show(string $decisionTraceId, GetDecisionTraceByIdAction $getTraceByIdAction): JsonResponse
    {
        $decisionTrace = $getTraceByIdAction->execute($decisionTraceId);

        if (!$decisionTrace) {
            return $this->buildTraceNotFoundResponse($decisionTraceId);
        }

        return $this->buildSuccessfulTraceResponse($decisionTrace);
    }

    public function acknowledge(string $decisionTraceId, AcknowledgeDecisionTraceAction $acknowledgeAction): JsonResponse
    {
        try {
            $decisionTrace = $acknowledgeAction->execute($decisionTraceId);

            if (!$decisionTrace) {
                return $this->buildTraceNotFoundResponse($decisionTraceId);
            }

            return $this->buildSuccessfulTraceResponse($decisionTrace);
        } catch (\InvalidArgumentException $domainValidationException) {
            return $this->buildInvalidStateTransitionResponse($domainValidationException->getMessage());
        }
    }

    public function actUpon(ActUponDecisionTraceRequest $request, string $decisionTraceId, ActUponDecisionTraceAction $actUponAction): JsonResponse
    {
        $validatedInput = $request->validated();
        $actorId = $validatedInput['actor_id'];

        try {
            $decisionTrace = $actUponAction->execute($decisionTraceId, $actorId);

            if (!$decisionTrace) {
                return $this->buildTraceNotFoundResponse($decisionTraceId);
            }

            return $this->buildSuccessfulTraceResponse($decisionTrace);
        } catch (\InvalidArgumentException $domainValidationException) {
            return $this->buildInvalidStateTransitionResponse($domainValidationException->getMessage());
        }
    }

    public function dismiss(DismissDecisionTraceRequest $request, string $decisionTraceId, DismissDecisionTraceAction $dismissAction): JsonResponse
    {
        $validatedInput = $request->validated();
        $actorId = $validatedInput['actor_id'];

        try {
            $decisionTrace = $dismissAction->execute($decisionTraceId, $actorId);

            if (!$decisionTrace) {
                return $this->buildTraceNotFoundResponse($decisionTraceId);
            }

            return $this->buildSuccessfulTraceResponse($decisionTrace);
        } catch (\InvalidArgumentException $domainValidationException) {
            return $this->buildInvalidStateTransitionResponse($domainValidationException->getMessage());
        }
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
