<?php

declare(strict_types=1);

namespace App\Modules\Locations\Application\Actions;

use App\Modules\Audit\Application\Services\AuditLogger;
use App\Modules\Events\Application\Services\EventPublisher;
use App\Modules\Locations\Application\DTOs\UpdateLocationStatusData;
use App\Modules\Locations\Domain\Entities\Location;
use App\Modules\Locations\Domain\Exceptions\LocationNotFound;
use App\Modules\Locations\Domain\Repositories\LocationRepository;
use Illuminate\Support\Facades\DB;

final class UpdateLocationStatusAction
{
    public function __construct(
        private readonly LocationRepository $locationRepository,
        private readonly AuditLogger $auditLogger,
        private readonly EventPublisher $eventPublisher,
    ) {}

    public function execute(UpdateLocationStatusData $command): Location
    {
        $warehouseLocation = $this->getWarehouseLocationOrThrow($command->locationId);

        if ($warehouseLocation->isBlocked() === $command->isBlocked) {
            return $warehouseLocation;
        }

        $updatedLocation = $this->buildUpdatedLocationEntity($warehouseLocation, $command->isBlocked);
        $this->persistStatusChange($command, $updatedLocation, $command->correlationId);

        return $updatedLocation;
    }

    private function getWarehouseLocationOrThrow(string $locationId): Location
    {
        $warehouseLocation = $this->locationRepository->findById($locationId);
        
        if (!$warehouseLocation) {
            throw LocationNotFound::withId($locationId);
        }
        
        return $warehouseLocation;
    }

    private function persistStatusChange(UpdateLocationStatusData $command, Location $updatedLocation, string $correlationId): void
    {
        $eventType = $command->isBlocked ? 'location.blocked' : 'location.unblocked';

        DB::transaction(function () use ($command, $updatedLocation, $correlationId, $eventType) {
            $this->locationRepository->save($updatedLocation);

            $this->logAuditTrail($command, $correlationId);
            $this->publishEvent($command, $correlationId, $eventType);
        });
    }

    private function logAuditTrail(UpdateLocationStatusData $command, string $correlationId): void
    {
        $auditAction = $command->isBlocked ? 'location.blocked' : 'location.unblocked';
        $changeset = ['isBlocked' => $command->isBlocked];
        
        if ($command->reason !== null) {
            $changeset['reason'] = $command->reason;
        }

        $this->auditLogger->log(
            action: $auditAction,
            entityType: 'WarehouseLocation',
            entityId: $command->locationId,
            changeset: $changeset,
            actorId: $command->performedBy,
            correlationId: $correlationId
        );
    }

    private function publishEvent(UpdateLocationStatusData $command, string $correlationId, string $eventType): void
    {
        $eventPayload = ['locationId' => $command->locationId];
        
        if ($command->isBlocked && $command->reason !== null) {
            $eventPayload['reason'] = $command->reason;
        }

        $this->eventPublisher->publish(
            eventType: $eventType,
            payload: $eventPayload,
            actorId: $command->performedBy,
            correlationId: $correlationId
        );
    }

    private function buildUpdatedLocationEntity(Location $warehouseLocation, bool $isBlocked): Location
    {
        return new Location(
            id: $warehouseLocation->id(),
            warehouseCode: $warehouseLocation->warehouseCode(),
            zone: $warehouseLocation->zone(),
            aisle: $warehouseLocation->aisle(),
            rack: $warehouseLocation->rack(),
            level: $warehouseLocation->level(),
            bin: $warehouseLocation->bin(),
            label: $warehouseLocation->label(),
            isBlocked: $isBlocked,
        );
    }
}
