<?php

declare(strict_types=1);

namespace App\Modules\Locations\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Locations\Application\Actions\CreateLocationAction;
use App\Modules\Locations\Application\Actions\GetLocationByIdAction;
use App\Modules\Locations\Application\Actions\ListLocationsAction;
use App\Modules\Locations\Application\Actions\UpdateLocationStatusAction;
use App\Modules\Locations\Application\DTOs\CreateLocationData;
use App\Modules\Locations\Application\DTOs\UpdateLocationStatusData;
use App\Modules\Locations\Infrastructure\Http\Requests\StoreLocationRequest;
use App\Modules\Locations\Infrastructure\Http\Requests\UpdateLocationStatusRequest;
use App\Modules\Locations\Infrastructure\Http\Resources\LocationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

final class LocationController extends Controller
{
    public function index(Request $request, ListLocationsAction $action): \Illuminate\Http\JsonResponse
    {
        $page = (int) $request->query('page', '1');
        $perPage = (int) $request->query('per_page', '50');

        $filters = array_filter([
            'warehouseCode' => $request->query('warehouseCode'),
            'zone' => $request->query('zone'),
            'aisle' => $request->query('aisle'),
            'rack' => $request->query('rack'),
            'bin' => $request->query('bin'),
        ]);

        $paginator = $action->execute($page, $perPage, $filters);

        return \App\Http\Responses\PaginatedResponse::make($paginator, LocationResource::class);
    }

    public function show(string $id, GetLocationByIdAction $action): JsonResponse
    {
        $location = $action->execute($id);

        return response()->json([
            'data' => new LocationResource($location),
        ]);
    }

    public function store(StoreLocationRequest $request, CreateLocationAction $action): JsonResponse
    {
        $location = $action->execute(
            CreateLocationData::fromArray($request->validated())
        );

        return response()->json([
            'data' => new LocationResource($location),
        ], 201);
    }

    public function updateStatus(string $id, UpdateLocationStatusRequest $request, UpdateLocationStatusAction $action): JsonResponse
    {
        try {
            $userId = $request->user() ? (string) $request->user()->id : 'system_user';

            $statusUpdate = new UpdateLocationStatusData(
                locationId: $id,
                isBlocked: (bool) $request->validated('isBlocked'),
                reason: $request->validated('reason'),
                performedBy: $userId,
            );

            $location = $action->execute($statusUpdate);

            return response()->json([
                'data' => [
                    'id' => $location->id(),
                    'isBlocked' => $location->isBlocked(),
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

