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
    public function index(Request $request, GetMovementsAction $action): JsonResponse
    {
        $page = (int) $request->query('page', '1');
        $perPage = (int) $request->query('per_page', '50');

        $filters = [
            'productId' => $request->query('productId'),
            'type' => $request->query('type'),
            'fromLocationId' => $request->query('fromLocationId'),
            'toLocationId' => $request->query('toLocationId'),
        ];

        $paginator = $action->execute($page, $perPage, array_filter($filters));

        return \App\Http\Responses\PaginatedResponse::make($paginator, MovementResource::class);
    }

    public function show(string $id, GetMovementByIdAction $action): JsonResponse
    {
        $movement = $action->execute($id);

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

    public function store(RegisterMovementRequest $request, RegisterMovementAction $action): JsonResponse
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
                idempotencyKey: $request->header('Idempotency-Key')
            );

            $movement = $action->execute($movementRegistration);

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
}
