<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Application\Actions\GetStockItemByIdAction;
use App\Modules\Inventory\Application\Actions\ListStockItemsAction;
use App\Modules\Inventory\Infrastructure\Http\Resources\StockItemResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class InventoryController extends Controller
{
    public function index(Request $request, ListStockItemsAction $action): JsonResponse
    {
        $page = (int) $request->query('page', '1');
        $perPage = (int) $request->query('per_page', '50');

        $filters = array_filter([
            'productId' => $request->query('productId'),
            'locationId' => $request->query('locationId'),
            'status' => $request->query('status'),
        ], fn ($value) => $value !== null);

        $paginator = $action->execute($page, $perPage, $filters);

        return \App\Http\Responses\PaginatedResponse::make($paginator, StockItemResource::class);
    }

    public function show(string $id, GetStockItemByIdAction $action): JsonResponse
    {
        $stockItem = $action->execute($id);

        return response()->json([
            'data' => new StockItemResource($stockItem),
        ]);
    }
}
