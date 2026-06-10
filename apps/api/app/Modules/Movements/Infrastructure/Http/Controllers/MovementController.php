<?php

declare(strict_types=1);

namespace App\Modules\Movements\Infrastructure\Http\Controllers;

use App\Modules\Movements\Application\Actions\GetMovementByIdAction;
use App\Modules\Movements\Application\Actions\GetMovementsAction;
use App\Modules\Movements\Application\Actions\RegisterMovementAction;
use App\Modules\Movements\Application\DTOs\RegisterMovementDTO;
use App\Modules\Movements\Infrastructure\Http\Requests\RegisterMovementRequest;
use App\Modules\Movements\Infrastructure\Http\Resources\MovementResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use App\Modules\Inventory\Domain\Exceptions\OptimisticLockException;

final class MovementController
{
    public function index(Request $request, GetMovementsAction $getMovementsAction): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'productId' => ['nullable', 'string'],
            'type' => ['nullable', 'string'],
            'fromLocationId' => ['nullable', 'string'],
            'toLocationId' => ['nullable', 'string'],
        ]);

        $page = (int) ($validated['page'] ?? 1);
        $perPage = (int) ($validated['per_page'] ?? 50);

        $filters = [
            'productId' => $validated['productId'] ?? null,
            'type' => $validated['type'] ?? null,
            'fromLocationId' => $validated['fromLocationId'] ?? null,
            'toLocationId' => $validated['toLocationId'] ?? null,
        ];

        $paginator = $getMovementsAction->execute($page, $perPage, array_filter($filters));

        return \App\Http\Responses\PaginatedResponse::make($paginator, MovementResource::class);
    }

    public function show(string $id, GetMovementByIdAction $getMovementByIdAction): JsonResponse
    {
        $movement = $getMovementByIdAction->execute($id);

        if (!$movement) {
            return response()->json([
                'error' => [
                    'code' => 'not_found',
                    'message' => "Movement [{$id}] not found."
                ]
            ], 404);
        }

        return response()->json([
            'data' => new MovementResource($movement)
        ]);
    }

    public function store(RegisterMovementRequest $request, RegisterMovementAction $registerMovementAction): JsonResponse
    {
        try {
            // Actor identity must come from session, never from request payload.
            $userId = $request->user()?->id ?? 'system_user';
            
            $movementRegistration = new RegisterMovementDTO(
                productId: $request->validated('productId'),
                fromLocationId: $request->validated('fromLocationId'),
                toLocationId: $request->validated('toLocationId'),
                type: $request->validated('type'),
                quantity: (int) $request->validated('quantity'),
                reference: $request->validated('reference'),
                lotNumber: $request->validated('lotNumber'),
                reason: $request->validated('reason'),
                performedBy: $userId,
                performedAt: now()->toIso8601String(),
                correlationId: $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                idempotencyKey: $request->header('Idempotency-Key')
            );

            $movement = $registerMovementAction->execute($movementRegistration);

            return response()->json([
                'data' => new MovementResource($movement)
            ], 201);

        } catch (OptimisticLockException $e) {
            return response()->json([
                'error' => [
                    'code' => 'conflict',
                    'message' => 'Resource was modified by another operation. Please retry.',
                ]
            ], 409);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'error' => [
                    'code' => 'validation_failed',
                    'message' => $e->getMessage()
                ]
            ], 422);
        }
    }
}
