<?php

declare(strict_types=1);

namespace App\Modules\Incidents\Infrastructure\Http\Controllers;

use App\Modules\Incidents\Application\Actions\GetIncidentByIdAction;
use App\Modules\Incidents\Application\Actions\GetIncidentsAction;
use App\Modules\Incidents\Application\Actions\ReportIncidentAction;
use App\Modules\Incidents\Application\Actions\UpdateIncidentMetadataAction;
use App\Modules\Incidents\Application\Actions\UpdateIncidentStatusAction;
use App\Modules\Incidents\Application\DTOs\ReportIncidentDTO;
use App\Modules\Incidents\Application\DTOs\UpdateIncidentMetadataDTO;
use App\Modules\Incidents\Application\DTOs\UpdateIncidentStatusDTO;
use App\Modules\Incidents\Application\Exceptions\IdempotencyConflictException;
use App\Modules\Incidents\Domain\Enums\IncidentStatus;
use App\Modules\Incidents\Infrastructure\Http\Requests\ReportIncidentRequest;
use App\Modules\Incidents\Infrastructure\Http\Requests\UpdateIncidentMetadataRequest;
use App\Modules\Incidents\Infrastructure\Http\Requests\UpdateIncidentStatusRequest;
use App\Modules\Incidents\Infrastructure\Http\Resources\IncidentResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

final class IncidentController
{
    public function listIncidents(Request $request, GetIncidentsAction $action): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'status' => ['nullable', 'string'],
            'type' => ['nullable', 'string'],
            'locationId' => ['nullable', 'string'],
            'productId' => ['nullable', 'string'],
        ]);

        $incidentFilters = array_filter([
            'status' => $validated['status'] ?? null,
            'type' => $validated['type'] ?? null,
            'locationId' => $validated['locationId'] ?? null,
            'productId' => $validated['productId'] ?? null,
        ]);

        $paginator = $action->execute(
            (int) ($validated['page'] ?? 1),
            (int) ($validated['per_page'] ?? 50),
            $incidentFilters
        );

        return \App\Http\Responses\PaginatedResponse::make($paginator, IncidentResource::class);
    }

    public function viewIncident(string $incidentId, GetIncidentByIdAction $action): JsonResponse
    {
        $incidentDetails = $action->execute($incidentId);

        if (!$incidentDetails) {
            return $this->buildIncidentNotFoundResponse($incidentId);
        }

        return response()->json([
            'data' => new IncidentResource($incidentDetails)
        ]);
    }

    public function reportIncident(ReportIncidentRequest $request, ReportIncidentAction $action): JsonResponse
    {
        try {
            $incidentReport = $this->buildReportIncidentDTO($request);
            $incidentDetails = $action->execute($incidentReport);

            return response()->json([
                'data' => new IncidentResource($incidentDetails)
            ], 201);

        } catch (InvalidArgumentException $e) {
            return $this->buildValidationFailedResponse($e->getMessage());
        } catch (IdempotencyConflictException $e) {
            return $this->handleIdempotencyConflict($e);
        }
    }

    public function transitionIncidentStatus(string $incidentId, UpdateIncidentStatusRequest $request, UpdateIncidentStatusAction $action): JsonResponse
    {
        try {
            $statusTransition = $this->buildUpdateIncidentStatusDTO($incidentId, $request);
            $incidentDetails = $action->execute($statusTransition);

            return response()->json([
                'data' => new IncidentResource($incidentDetails)
            ]);

        } catch (InvalidArgumentException $e) {
            if (str_contains($e->getMessage(), 'not found')) {
                return $this->buildIncidentNotFoundResponse($incidentId);
            }
            return $this->buildValidationFailedResponse($e->getMessage());
        }
    }

    public function updateIncidentMetadata(string $incidentId, UpdateIncidentMetadataRequest $request, UpdateIncidentMetadataAction $action): JsonResponse
    {
        try {
            $metadataUpdate = $this->buildUpdateIncidentMetadataDTO($incidentId, $request);
            $incidentDetails = $action->execute($metadataUpdate);

            return response()->json([
                'data' => new IncidentResource($incidentDetails)
            ]);

        } catch (InvalidArgumentException $e) {
            if (str_contains($e->getMessage(), 'not found')) {
                return $this->buildIncidentNotFoundResponse($incidentId);
            }
            return $this->buildValidationFailedResponse($e->getMessage());
        }
    }

    private function buildReportIncidentDTO(ReportIncidentRequest $request): ReportIncidentDTO
    {
        $userId = $request->user() ? (string) $request->user()->id : 'system_user';
        
        return new ReportIncidentDTO(
            productId: $request->validated('productId'),
            locationId: $request->validated('locationId'),
            type: $request->validated('type'),
            severity: $request->validated('severity'),
            description: $request->validated('description'),
            quantityAffected: $request->validated('quantityAffected') !== null ? (int) $request->validated('quantityAffected') : null,
            reportedBy: $userId,
            correlationId: $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
            idempotencyKey: $request->header('Idempotency-Key')
        );
    }

    private function buildUpdateIncidentStatusDTO(string $incidentId, UpdateIncidentStatusRequest $request): UpdateIncidentStatusDTO
    {
        $userId = $request->user() ? (string) $request->user()->id : 'system_user';
        
        $status = IncidentStatus::tryFrom($request->validated('status'));
        if (!$status) {
            throw new InvalidArgumentException("Invalid incident status: {$request->validated('status')}.");
        }

        return new UpdateIncidentStatusDTO(
            incidentId: $incidentId,
            incidentStatus: $status,
            performedBy: $userId,
            correlationId: $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString())
        );
    }

    private function buildUpdateIncidentMetadataDTO(string $incidentId, UpdateIncidentMetadataRequest $request): UpdateIncidentMetadataDTO
    {
        $userId = $request->user() ? (string) $request->user()->id : 'system_user';

        return new UpdateIncidentMetadataDTO(
            incidentId: $incidentId,
            notes: $request->validated('notes'),
            assignedTo: $request->validated('assignedTo'),
            performedBy: $userId,
        );
    }

    private function buildIncidentNotFoundResponse(string $incidentId): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => 'not_found',
                'message' => "Incident [{$incidentId}] not found."
            ]
        ], 404);
    }

    private function buildValidationFailedResponse(string $message): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => 'validation_failed',
                'message' => $message
            ]
        ], 422);
    }

    private function handleIdempotencyConflict(IdempotencyConflictException $e): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => 'conflict',
                'message' => $e->getMessage(),
            ]
        ], 409);
    }
}
