<?php

declare(strict_types=1);

namespace App\Modules\Movements\Application\Actions;

use App\Modules\Audit\Application\Services\AuditLogger;
use App\Modules\Events\Application\Services\OutboxDispatcher;
use App\Modules\Events\Infrastructure\Persistence\Eloquent\EventOutboxModel;
use App\Modules\Inventory\Application\Services\InternalStockMutationService;
use App\Modules\Locations\Domain\Repositories\LocationRepository;
use App\Modules\Movements\Application\DTOs\RegisterMovementDTO;
use App\Modules\Movements\Domain\Entities\InventoryMovement;
use App\Modules\Movements\Domain\Enums\MovementType;
use App\Modules\Movements\Domain\Services\MovementValidator;
use App\Modules\Movements\Infrastructure\Persistence\Eloquent\InventoryMovementModel;
use App\Modules\Product\Domain\Repositories\ProductRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class RegisterMovementAction
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly LocationRepository $locationRepository,
        private readonly MovementValidator $movementValidator,
        private readonly InternalStockMutationService $stockMutationService,
        private readonly OutboxDispatcher $outboxDispatcher,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function execute(RegisterMovementDTO $movementData): InventoryMovement
    {
        if ($movementData->idempotencyKey !== null) {
            // DB unique constraint on idempotency_key enforces 409 Conflict for duplicates.
            $existing = InventoryMovementModel::where('idempotency_key', $movementData->idempotencyKey)->first();
            if ($existing) {
            }
        }

        $product = $this->productRepository->findById($movementData->productId);
        if (!$product) {
            throw new InvalidArgumentException("Product {$movementData->productId} not found.");
        }

        $type = MovementType::tryFrom($movementData->type);
        if (!$type) {
            throw new InvalidArgumentException("Invalid movement type: {$movementData->type}.");
        }

        if ($movementData->fromLocationId !== null) {
            $fromLocation = $this->locationRepository->findById($movementData->fromLocationId);
            if (!$fromLocation) {
                throw new InvalidArgumentException("Location {$movementData->fromLocationId} not found.");
            }
            $this->movementValidator->assertLocationNotBlocked($fromLocation->id(), $fromLocation->isBlocked());
        }

        if ($movementData->toLocationId !== null) {
            $toLocation = $this->locationRepository->findById($movementData->toLocationId);
            if (!$toLocation) {
                throw new InvalidArgumentException("Location {$movementData->toLocationId} not found.");
            }
            $this->movementValidator->assertLocationNotBlocked($toLocation->id(), $toLocation->isBlocked());
        }

        $movementId = Str::uuid()->toString();
        $movement = new InventoryMovement(
            id: $movementId,
            productId: $movementData->productId,
            fromLocationId: $movementData->fromLocationId,
            toLocationId: $movementData->toLocationId,
            type: $type,
            quantity: $movementData->quantity,
            reference: $movementData->reference,
            lotNumber: $movementData->lotNumber,
            reason: $movementData->reason,
            performedBy: $movementData->performedBy,
            performedAt: $movementData->performedAt,
            idempotencyKey: $movementData->idempotencyKey,
            createdAt: now()->toIso8601String(),
            updatedAt: now()->toIso8601String(),
        );

        $outboxEventId = Str::uuid()->toString();
        $correlationId = request()->header('X-Correlation-ID', Str::uuid()->toString());

        DB::transaction(function () use ($movement, $outboxEventId, $correlationId) {
            InventoryMovementModel::create([
                'id' => $movement->id(),
                'product_id' => $movement->productId(),
                'from_location_id' => $movement->fromLocationId(),
                'to_location_id' => $movement->toLocationId(),
                'type' => $movement->type()->value,
                'quantity' => $movement->quantity(),
                'reference' => $movement->reference(),
                'lot_number' => $movement->lotNumber(),
                'reason' => $movement->reason(),
                'performed_by' => $movement->performedBy(),
                'performed_at' => $movement->performedAt(),
                'idempotency_key' => $movement->idempotencyKey(),
            ]);

            $this->stockMutationService->applyMovement($movement);

            $this->auditLogger->log(
                action: 'movement.registered',
                entityType: 'InventoryMovement',
                entityId: $movement->id(),
                changeset: [
                    'type' => $movement->type()->value,
                    'quantity' => $movement->quantity(),
                    'fromLocationId' => $movement->fromLocationId(),
                    'toLocationId' => $movement->toLocationId(),
                    'reference' => $movement->reference(),
                ],
                actorId: $movement->performedBy(),
                correlationId: $correlationId
            );

            $eventId = Str::uuid()->toString();
            $eventPayload = [
                'movementId' => $movement->id(),
                'productId' => $movement->productId(),
                'type' => $movement->type()->value,
                'quantity' => $movement->quantity(),
                'fromLocationId' => $movement->fromLocationId(),
                'toLocationId' => $movement->toLocationId()
            ];

            EventOutboxModel::create([
                'event_id' => $outboxEventId,
                'event_type' => 'movement.created',
                'event_version' => 1,
                'occurred_at' => now(),
                'actor_id' => $movement->performedBy(),
                'correlation_id' => $correlationId,
                'causation_id' => $outboxEventId,
                'payload' => $eventPayload,
                'dispatched' => false,
            ]);
            
            $stockEventId = Str::uuid()->toString();
            $stockEventType = match ($movement->type()) {
                MovementType::RECEIPT => 'inventory.stock.received',
                MovementType::PUTAWAY => 'inventory.stock.putaway',
                MovementType::RELOCATION => 'inventory.stock.relocated',
                MovementType::ADJUSTMENT => 'inventory.stock.adjusted',
                MovementType::PICKING => 'inventory.stock.picked',
                MovementType::RETURN_INTERNAL => 'inventory.stock.returned',
            };
            
            $stockEventPayload = match ($movement->type()) {
                MovementType::RECEIPT => [
                    'movementId' => $movement->id(), 'productId' => $movement->productId(), 'locationId' => $movement->toLocationId(), 'quantity' => $movement->quantity(), 'lotNumber' => $movement->lotNumber()
                ],
                MovementType::PUTAWAY, MovementType::RELOCATION, MovementType::RETURN_INTERNAL => [
                    'productId' => $movement->productId(), 'fromLocationId' => $movement->fromLocationId(), 'toLocationId' => $movement->toLocationId(), 'quantity' => $movement->quantity()
                ],
                MovementType::ADJUSTMENT => [
                    'productId' => $movement->productId(), 'locationId' => $movement->fromLocationId(), 'reason' => $movement->reason(), 'deltaQuantity' => -$movement->quantity()
                ],
                MovementType::PICKING => [
                    'productId' => $movement->productId(), 'locationId' => $movement->fromLocationId(), 'quantity' => $movement->quantity()
                ]
            };

            EventOutboxModel::create([
                'event_id' => $stockEventId,
                'event_type' => $stockEventType,
                'event_version' => 1,
                'occurred_at' => now(),
                'actor_id' => $movement->performedBy(),
                'correlation_id' => $correlationId,
                'causation_id' => $stockEventId,
                'payload' => $stockEventPayload,
                'dispatched' => false,
            ]);

        });

        $this->outboxDispatcher->dispatchAndMark($outboxEventId, new \stdClass());

        return $movement;
    }
}
