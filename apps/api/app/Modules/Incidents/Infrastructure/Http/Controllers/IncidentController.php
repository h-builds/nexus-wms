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
use App\Modules\Incidents\Infrastructure\Http\Requests\ReportIncidentRequest;
use App\Modules\Incidents\Infrastructure\Http\Requests\UpdateIncidentMetadataRequest;
use App\Modules\Incidents\Infrastructure\Http\Requests\UpdateIncidentStatusRequest;
use App\Modules\Incidents\Infrastructure\Http\Resources\IncidentResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

final class IncidentController
{
    public function index(Request $request, GetIncidentsAction $action): JsonResponse
    {
        $page = (int) $request->query('page', '1');
        $perPage = (int) $request->query('per_page', '50');

        $filters = [
            'status' => $request->query('status'),
            'type' => $request->query('type'),
            'locationId' => $request->query('locationId'),
            'productId' => $request->query('productId'),
        ];

        $paginator = $action->execute($page, $perPage, array_filter($filters));

        return \App\Http\Responses\PaginatedResponse::make($paginator, IncidentResource::class);
    }

    public function show(string $id, GetIncidentByIdAction $action): JsonResponse
    {
        $incident = $action->execute($id);

        if (!$incident) {
            return response()->json([
                'error' => [
                    'code' => 'not_found',
                    'message' => "Incident [{$id}] not found."
                ]
            ], 404);
        }

        return response()->json([
            'data' => new IncidentResource($incident)
        ]);
    }

    public function store(ReportIncidentRequest $request, ReportIncidentAction $action): JsonResponse
    {
        try {
            $userId = $request->user() ? (string) $request->user()->id : 'system_user';
            
            $incidentReport = new ReportIncidentDTO(
                productId: $request->validated('productId'),
                locationId: $request->validated('locationId'),
                type: $request->validated('type'),
                severity: $request->validated('severity'),
                description: $request->validated('description'),
                quantityAffected: $request->validated('quantityAffected') !== null ? (int) $request->validated('quantityAffected') : null,
                reportedBy: $userId,
                idempotencyKey: $request->header('Idempotency-Key')
            );

            $incident = $action->execute($incidentReport);

            return response()->json([
                'data' => new IncidentResource($incident)
            ], 201);

        } catch (InvalidArgumentException $e) {
            return response()->json([
                'error' => [
                    'code' => 'validation_failed',
                    'message' => $e->getMessage()
                ]
            ], 422);
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '23000' || $e->getCode() === '23505' || $e->getCode() === '19') {
                return response()->json([
                    'error' => [
                        'code' => 'conflict',
                        'message' => 'Idempotency key already processed.',
                    ]
                ], 409);
            }
            throw $e;
        }
    }

    public function updateStatus(string $id, UpdateIncidentStatusRequest $request, UpdateIncidentStatusAction $action): JsonResponse
    {
        try {
            $userId = $request->user() ? (string) $request->user()->id : 'system_user';
            
            $statusTransition = new UpdateIncidentStatusDTO(
                incidentId: $id,
                status: $request->validated('status'),
                performedBy: $userId
            );

            $incident = $action->execute($statusTransition);

            return response()->json([
                'data' => new IncidentResource($incident)
            ]);

        } catch (InvalidArgumentException $e) {
            if (str_contains($e->getMessage(), 'not found')) {
                return response()->json([
                    'error' => [
                        'code' => 'not_found',
                        'message' => $e->getMessage()
                    ]
                ], 404);
            }
            
            return response()->json([
                'error' => [
                    'code' => 'validation_failed',
                    'message' => $e->getMessage()
                ]
            ], 422);
        }
    }

    public function update(string $id, UpdateIncidentMetadataRequest $request, UpdateIncidentMetadataAction $action): JsonResponse
    {
        try {
            $userId = $request->user() ? (string) $request->user()->id : 'system_user';

            $metadataUpdate = new UpdateIncidentMetadataDTO(
                incidentId: $id,
                notes: $request->validated('notes'),
                assignedTo: $request->validated('assignedTo'),
                performedBy: $userId,
            );

            $incident = $action->execute($metadataUpdate);

            return response()->json([
                'data' => [
                    'id' => $incident->id(),
                    'notes' => $incident->notes(),
                    'updatedAt' => $incident->updatedAt(),
                ],
            ]);

        } catch (InvalidArgumentException $e) {
            if (str_contains($e->getMessage(), 'not found')) {
                return response()->json([
                    'error' => [
                        'code' => 'not_found',
                        'message' => $e->getMessage(),
                    ]
                ], 404);
            }

            return response()->json([
                'error' => [
                    'code' => 'validation_failed',
                    'message' => $e->getMessage(),
                ]
            ], 422);
        }
    }
}
