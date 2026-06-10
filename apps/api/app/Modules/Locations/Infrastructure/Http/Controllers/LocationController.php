<?php

declare(strict_types=1);

namespace App\Modules\Locations\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Locations\Application\Actions\CreateLocationAction;
use App\Modules\Locations\Application\Actions\GetLocationByIdAction;
use App\Modules\Locations\Application\Actions\ListLocationsAction;
use App\Modules\Locations\Application\Actions\UpdateLocationStatusAction;
use App\Modules\Locations\Application\DTOs\CreateLocationDTO;
use App\Modules\Locations\Application\DTOs\LocationListCriteria;
use App\Modules\Locations\Application\DTOs\UpdateLocationStatusDTO;
use App\Modules\Locations\Domain\Exceptions\LocationNotFound;
use App\Modules\Locations\Infrastructure\Http\Requests\StoreLocationRequest;
use App\Modules\Locations\Infrastructure\Http\Requests\ListLocationsRequest;
use App\Modules\Locations\Infrastructure\Http\Requests\UpdateLocationStatusRequest;
use App\Modules\Locations\Infrastructure\Http\Resources\LocationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class LocationController extends Controller
{
    public function index(ListLocationsRequest $request, ListLocationsAction $action): JsonResponse
    {
        $paginator = $action->execute(
            $request->getPage(),
            $request->getPerPage(),
            $request->toCriteria()
        );

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
            new CreateLocationDTO(
                warehouseCode: $request->validated('warehouseCode'),
                zone: $request->validated('zone'),
                aisle: $request->validated('aisle'),
                rack: $request->validated('rack'),
                level: $request->validated('level'),
                bin: $request->validated('bin'),
                correlationId: $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                actorId: $request->user()?->id !== null ? (string) $request->user()?->id : null,
            )
        );

        return response()->json([
            'data' => new LocationResource($location),
        ], 201);
    }

    public function updateStatus(string $id, UpdateLocationStatusRequest $request, UpdateLocationStatusAction $action): JsonResponse
    {
        try {
            $statusUpdate = $request->toDTO($id);

            $location = $action->execute($statusUpdate);

            return response()->json([
                'data' => new LocationResource($location),
            ]);

        } catch (LocationNotFound $e) {
            return response()->json([
                'error' => [
                    'code' => 'not_found',
                    'message' => $e->getMessage(),
                ]
            ], 404);
        }
    }
}

