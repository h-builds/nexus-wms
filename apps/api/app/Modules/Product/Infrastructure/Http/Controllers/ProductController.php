<?php

declare(strict_types=1);

namespace App\Modules\Product\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Product\Application\Actions\CreateProductAction;
use App\Modules\Product\Application\Actions\GetProductByIdAction;
use App\Modules\Product\Application\Actions\ListProductsAction;
use App\Modules\Product\Application\DTOs\CreateProductData;
use App\Modules\Product\Infrastructure\Http\Requests\StoreProductRequest;
use App\Modules\Product\Infrastructure\Http\Resources\ProductResource;
use Illuminate\Http\JsonResponse;

final class ProductController extends Controller
{
    public function index(ListProductsAction $action): JsonResponse
    {
        $products = $action->execute();

        return response()->json([
            'data' => ProductResource::collection(collect($products)),
        ]);
    }

    public function show(string $id, GetProductByIdAction $action): JsonResponse
    {
        $product = $action->execute($id);

        return response()->json([
            'data' => new ProductResource($product),
        ]);
    }

    public function store(StoreProductRequest $request, CreateProductAction $action): JsonResponse
    {
        $product = $action->execute(
            CreateProductData::fromArray($request->validated())
        );

        return response()->json([
            'data' => new ProductResource($product),
        ], 201);
    }
}