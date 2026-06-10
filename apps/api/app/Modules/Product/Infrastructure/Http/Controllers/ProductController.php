<?php

declare(strict_types=1);

namespace App\Modules\Product\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Responses\PaginatedResponse;
use App\Modules\Product\Application\Actions\CreateProductAction;
use App\Modules\Product\Application\Actions\GetProductByIdAction;
use App\Modules\Product\Application\Actions\ListProductsAction;
use App\Modules\Product\Application\DTOs\CreateProductPayload;
use App\Modules\Product\Infrastructure\Http\Requests\StoreProductRequest;
use App\Modules\Product\Infrastructure\Http\Resources\ProductResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class ProductController extends Controller
{
    public function index(Request $request, ListProductsAction $listProducts): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sku' => ['sometimes', 'string'],
            'q' => ['sometimes', 'string'],
        ]);

        $page = (int) ($validated['page'] ?? 1);
        $perPage = (int) ($validated['per_page'] ?? 50);

        $filters = array_filter([
            'sku' => $validated['sku'] ?? null,
            'q' => $validated['q'] ?? null,
        ]);

        $paginator = $listProducts->execute($page, $perPage, $filters);

        return PaginatedResponse::make($paginator, ProductResource::class);
    }

    public function show(string $productId, GetProductByIdAction $getProductById): JsonResponse
    {
        $product = $getProductById->execute($productId);

        return (new ProductResource($product))->response();
    }

    public function store(StoreProductRequest $request, CreateProductAction $createProduct): JsonResponse
    {
        $correlationId = (string) $request->header('X-Correlation-ID', Str::uuid()->toString());

        $payload = CreateProductPayload::fromArray(array_merge(
            $request->validated(),
            ['correlationId' => $correlationId]
        ));

        $product = $createProduct->execute($payload);

        return (new ProductResource($product))->response()->setStatusCode(201);
    }
}