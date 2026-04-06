<?php

declare(strict_types=1);

namespace App\Modules\Movements\Application\Actions;

use App\Modules\Audit\Application\Services\AuditLogger;
use App\Modules\Events\Application\Services\EventPublisher;
use App\Modules\Inventory\Application\Services\InternalStockMutationService;
use App\Modules\Locations\Domain\Exceptions\LocationNotFound;
use App\Modules\Locations\Domain\Repositories\LocationRepository;
use App\Modules\Movements\Application\DTOs\RegisterMovementDTO;
use App\Modules\Movements\Domain\Entities\InventoryMovement;
use App\Modules\Movements\Domain\Enums\MovementType;
use App\Modules\Movements\Domain\Exceptions\InvalidMovementType;
use App\Modules\Movements\Domain\Services\MovementValidator;
use App\Modules\Movements\Domain\Repositories\MovementRepository;
use App\Modules\Product\Domain\Exceptions\ProductNotFound;
use App\Modules\Product\Domain\Repositories\ProductRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

    public function execute(RegisterMovementDTO $command): InventoryMovement
    {
        if ($command->idempotencyKey !== null) {
            $existingMovement = $this->movementRepository->findByIdempotencyKey($command->idempotencyKey);
            if ($existingMovement) {
                return $existingMovement;
            }
        }

        $this->ensureProductExists($command->productId);
        
        $movementType = $this->parseMovementType($command->type);
        
        $this->ensureLocationStateIsValid($command->fromLocationId);
        $this->ensureLocationStateIsValid($command->toLocationId);

        $inventoryMovement = $this->buildMovementEntity($command, $movementType);

        $this->persistMovementAndEvents($inventoryMovement, $command->correlationId);

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

    private function buildMovementEntity(RegisterMovementDTO $command, MovementType $movementType): InventoryMovement
    {
        return new InventoryMovement(
            id: Str::uuid()->toString(),
            productId: $command->productId,
            fromLocationId: $command->fromLocationId,
            toLocationId: $command->toLocationId,
            type: $movementType,
            quantity: $command->quantity,
            reference: $command->reference,
            lotNumber: $command->lotNumber,
            reason: $command->reason,
            performedBy: $command->performedBy,
            performedAt: $command->performedAt,
            idempotencyKey: $command->idempotencyKey,
            createdAt: now()->toIso8601String(),
            updatedAt: now()->toIso8601String(),
        );
    }

    private function persistMovementAndEvents(InventoryMovement $inventoryMovement, string $correlationId): void
    {
        DB::transaction(function () use ($inventoryMovement, $correlationId) {
            $this->storeMovementRecord($inventoryMovement);
            
            $this->stockMutationService->applyMovement($inventoryMovement);

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
        $eventPayload = [
            'movementId' => $inventoryMovement->id(),
            'productId' => $inventoryMovement->productId(),
            'type' => $inventoryMovement->type()->value,
            'quantity' => $inventoryMovement->quantity(),
            'fromLocationId' => $inventoryMovement->fromLocationId(),
            'toLocationId' => $inventoryMovement->toLocationId()
        ];

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
            MovementType::RECEIPT => [
                'movementId' => $inventoryMovement->id(), 'productId' => $inventoryMovement->productId(), 'locationId' => $inventoryMovement->toLocationId(), 'quantity' => $inventoryMovement->quantity(), 'lotNumber' => $inventoryMovement->lotNumber()
            ],
            MovementType::PUTAWAY, MovementType::RELOCATION, MovementType::RETURN_INTERNAL => [
                'productId' => $inventoryMovement->productId(), 'fromLocationId' => $inventoryMovement->fromLocationId(), 'toLocationId' => $inventoryMovement->toLocationId(), 'quantity' => $inventoryMovement->quantity()
            ],
            MovementType::ADJUSTMENT => $this->buildAdjustmentPayload($inventoryMovement),
            MovementType::PICKING => [
                'productId' => $inventoryMovement->productId(), 'locationId' => $inventoryMovement->fromLocationId(), 'quantity' => $inventoryMovement->quantity()
            ]
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
    private function buildAdjustmentPayload(InventoryMovement $inventoryMovement): array
    {
        $newQuantity = $this->stockMutationService->getQuantityOnHand(
            productId: $inventoryMovement->productId(),
            locationId: $inventoryMovement->fromLocationId(),
            lotNumber: $inventoryMovement->lotNumber()
        );

        $previousQuantity = $newQuantity + $inventoryMovement->quantity();

        return [
            'productId' => $inventoryMovement->productId(),
            'locationId' => $inventoryMovement->fromLocationId(),
            'previousQuantity' => $previousQuantity,
            'newQuantity' => $newQuantity,
            'reason' => $inventoryMovement->reason(),
        ];
    }
}
