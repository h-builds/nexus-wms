<?php

declare(strict_types=1);

namespace App\Modules\Locations\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Locations\Application\Actions\CreateLocationAction;
use App\Modules\Locations\Application\Actions\GetLocationByIdAction;
use App\Modules\Locations\Application\Actions\ListLocationsAction;
use App\Modules\Locations\Application\DTOs\CreateLocationData;
use App\Modules\Locations\Infrastructure\Http\Requests\StoreLocationRequest;
use App\Modules\Locations\Infrastructure\Http\Resources\LocationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
}
