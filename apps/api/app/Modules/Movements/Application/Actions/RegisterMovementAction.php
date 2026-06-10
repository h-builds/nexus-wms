<?php

declare(strict_types=1);

namespace App\Modules\Movements\Application\Actions;

use App\Modules\Audit\Application\Services\AuditLogger;
use App\Modules\Events\Application\Services\EventPublisher;
use App\Modules\Inventory\Application\DTOs\StockMutationDTO;
use App\Modules\Inventory\Application\Services\InternalStockMutationService;
use App\Modules\Inventory\Domain\Enums\MutationOperation;
use App\Modules\Locations\Domain\Exceptions\LocationNotFound;
use App\Modules\Locations\Domain\Repositories\LocationRepository;
use App\Modules\Movements\Application\DTOs\MovementCreatedEventPayload;
use App\Modules\Movements\Application\DTOs\RegisterMovementDTO;
use App\Modules\Movements\Application\DTOs\StockAdjustedEventPayload;
use App\Modules\Movements\Application\DTOs\StockMovedEventPayload;
use App\Modules\Movements\Application\DTOs\StockPickedEventPayload;
use App\Modules\Movements\Application\DTOs\StockReceivedEventPayload;
use App\Modules\Movements\Domain\Entities\InventoryMovement;

final class RegisterMovementAction
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly LocationRepository $locationRepository,
        private readonly MovementRepository $movementRepository,
        private readonly MovementValidator $movementValidator,
        private readonly InternalStockMutationService $stockMutationService,
        private readonly EventPublisher $eventPublisher,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function execute(RegisterMovementDTO $payload): InventoryMovement
    {
        if ($payload->idempotencyKey !== null) {
            $existingMovement = $this->movementRepository->findByIdempotencyKey($payload->idempotencyKey);
            if ($existingMovement) {
                return $existingMovement;
            }
        }

        $this->ensureProductExists($payload->productId);
        
        $movementType = $this->parseMovementType($payload->type);
        
        $this->ensureLocationStateIsValid($payload->fromLocationId);
        $this->ensureLocationStateIsValid($payload->toLocationId);

        $inventoryMovement = $this->buildMovementEntity($payload, $movementType);

        $this->persistMovementAndEvents($inventoryMovement, $payload->correlationId);

        return $inventoryMovement;
    }



    private function ensureProductExists(string $productId): void
    {
        $stockItemProduct = $this->productRepository->findById($productId);
        if (!$stockItemProduct) {
            throw ProductNotFound::withId($productId);
        }
    }

    private function parseMovementType(string $typeToken): MovementType
    {
        $movementType = MovementType::tryFrom($typeToken);
        if (!$movementType) {
            throw InvalidMovementType::withType($typeToken);
        }
        return $movementType;
    }

    private function ensureLocationStateIsValid(?string $locationId): void
    {
        if ($locationId !== null) {
            $warehouseLocation = $this->locationRepository->findById($locationId);
            if (!$warehouseLocation) {
                throw LocationNotFound::withId($locationId);
            }
            $this->movementValidator->assertLocationNotBlocked($warehouseLocation->id(), $warehouseLocation->isBlocked());
        }
    }

    private function buildMovementEntity(RegisterMovementDTO $payload, MovementType $movementType): InventoryMovement
    {
        return new InventoryMovement(
            id: Str::uuid()->toString(),
            productId: $payload->productId,
            fromLocationId: $payload->fromLocationId,
            toLocationId: $payload->toLocationId,
            type: $movementType,
            quantity: $payload->quantity,
            reference: $payload->reference,
            lotNumber: $payload->lotNumber,
            reason: $payload->reason,
            performedBy: $payload->performedBy,
            performedAt: $payload->performedAt,
            idempotencyKey: $payload->idempotencyKey,
            createdAt: now()->toIso8601String(),
            updatedAt: now()->toIso8601String(),
        );
    }

    private function persistMovementAndEvents(InventoryMovement $inventoryMovement, string $correlationId): void
    {
        DB::transaction(function () use ($inventoryMovement, $correlationId) {
            $this->storeMovementRecord($inventoryMovement);
            
            $mutation = new StockMutationDTO(
                operation: match ($inventoryMovement->type()) {
                    MovementType::RECEIPT, MovementType::RETURN_INTERNAL => MutationOperation::ADD,
                    MovementType::ADJUSTMENT, MovementType::PICKING => MutationOperation::SUBTRACT,
                    MovementType::RELOCATION, MovementType::PUTAWAY => MutationOperation::MOVE,
                },
                productId: $inventoryMovement->productId(),
                quantity: $inventoryMovement->quantity(),
                fromLocationId: $inventoryMovement->fromLocationId(),
                toLocationId: $inventoryMovement->toLocationId(),
                lotNumber: $inventoryMovement->lotNumber(),
            );
            $this->stockMutationService->applyMutation($mutation);

            $this->logMovementAudit($inventoryMovement, $correlationId);

            $this->publishMovementCreatedEvent($inventoryMovement, $correlationId);
            
            $this->publishStockEvent($inventoryMovement, $correlationId);
        });
    }

    private function storeMovementRecord(InventoryMovement $inventoryMovement): void
    {
        $this->movementRepository->save($inventoryMovement);
    }

    private function logMovementAudit(InventoryMovement $inventoryMovement, string $correlationId): void
    {
        $this->auditLogger->log(
            action: 'movement.registered',
            entityType: 'InventoryMovement',
            entityId: $inventoryMovement->id(),
            changeset: [
                'type' => $inventoryMovement->type()->value,
                'quantity' => $inventoryMovement->quantity(),
                'fromLocationId' => $inventoryMovement->fromLocationId(),
                'toLocationId' => $inventoryMovement->toLocationId(),
                'reference' => $inventoryMovement->reference(),
            ],
            actorId: $inventoryMovement->performedBy(),
            correlationId: $correlationId
        );
    }

    private function publishMovementCreatedEvent(InventoryMovement $inventoryMovement, string $correlationId): void
    {
        $eventPayload = new MovementCreatedEventPayload(
            movementId: $inventoryMovement->id(),
            productId: $inventoryMovement->productId(),
            type: $inventoryMovement->type()->value,
            quantity: $inventoryMovement->quantity(),
            fromLocationId: $inventoryMovement->fromLocationId(),
            toLocationId: $inventoryMovement->toLocationId()
        );

        $this->eventPublisher->publish(
            eventType: 'movement.created',
            payload: $eventPayload,
            actorId: $inventoryMovement->performedBy(),
            correlationId: $correlationId
        );
    }

    private function publishStockEvent(InventoryMovement $inventoryMovement, string $correlationId): void
    {
        $stockEventType = match ($inventoryMovement->type()) {
            MovementType::RECEIPT => 'inventory.stock.received',
            MovementType::PUTAWAY => 'inventory.stock.putaway',
            MovementType::RELOCATION => 'inventory.stock.relocated',
            MovementType::ADJUSTMENT => 'inventory.stock.adjusted',
            MovementType::PICKING => 'inventory.stock.picked',
            MovementType::RETURN_INTERNAL => 'inventory.stock.returned',
        };
        
        $stockEventPayload = match ($inventoryMovement->type()) {
            MovementType::RECEIPT => new StockReceivedEventPayload(
                movementId: $inventoryMovement->id(),
                productId: $inventoryMovement->productId(),
                locationId: $inventoryMovement->toLocationId(),
                quantity: $inventoryMovement->quantity(),
                lotNumber: $inventoryMovement->lotNumber()
            ),
            MovementType::PUTAWAY, MovementType::RELOCATION, MovementType::RETURN_INTERNAL => new StockMovedEventPayload(
                productId: $inventoryMovement->productId(),
                fromLocationId: $inventoryMovement->fromLocationId(),
                toLocationId: $inventoryMovement->toLocationId(),
                quantity: $inventoryMovement->quantity()
            ),
            MovementType::ADJUSTMENT => $this->buildAdjustmentPayload($inventoryMovement),
            MovementType::PICKING => new StockPickedEventPayload(
                productId: $inventoryMovement->productId(),
                locationId: $inventoryMovement->fromLocationId(),
                quantity: $inventoryMovement->quantity()
            )
        };

        $this->eventPublisher->publish(
            eventType: $stockEventType,
            payload: $stockEventPayload,
            actorId: $inventoryMovement->performedBy(),
            correlationId: $correlationId
        );
    }

    /**
     * Reads post-mutation stock to build EVENT_CATALOG-compliant adjustment payload
     * with previousQuantity and newQuantity instead of deltaQuantity.
     */
    private function buildAdjustmentPayload(InventoryMovement $inventoryMovement): StockAdjustedEventPayload
    {
        $newQuantity = $this->stockMutationService->getQuantityOnHand(
            productId: $inventoryMovement->productId(),
            locationId: $inventoryMovement->fromLocationId(),
            lotNumber: $inventoryMovement->lotNumber()
        );

        $previousQuantity = $newQuantity + $inventoryMovement->quantity();

        return new StockAdjustedEventPayload(
            productId: $inventoryMovement->productId(),
            locationId: $inventoryMovement->fromLocationId(),
            previousQuantity: $previousQuantity,
            newQuantity: $newQuantity,
            reason: $inventoryMovement->reason(),
        );
    }
}
