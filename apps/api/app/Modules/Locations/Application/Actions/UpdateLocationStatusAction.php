<?php

declare(strict_types=1);

namespace App\Modules\Locations\Application\Actions;

use App\Modules\Audit\Application\Services\AuditLogger;
use App\Modules\Events\Infrastructure\Persistence\Eloquent\EventOutboxModel;
use App\Modules\Locations\Application\DTOs\UpdateLocationStatusData;
use App\Modules\Locations\Domain\Entities\Location;
use App\Modules\Locations\Domain\Repositories\LocationRepository;
use App\Modules\Locations\Infrastructure\Persistence\Eloquent\LocationModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class UpdateLocationStatusAction
{
    public function __construct(
        private readonly LocationRepository $locationRepository,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function execute(UpdateLocationStatusData $data): Location
    {
        $location = $this->locationRepository->findById($data->locationId);
        if (!$location) {
            throw new InvalidArgumentException("Location {$data->locationId} not found.");
        }

        if ($location->isBlocked() === $data->isBlocked) {
            return $location;
        }

        $outboxEventId = Str::uuid()->toString();
        $correlationId = request()->header('X-Correlation-ID', Str::uuid()->toString());
        $eventType = $data->isBlocked ? 'location.blocked' : 'location.unblocked';

        DB::transaction(function () use ($data, $location, $outboxEventId, $correlationId, $eventType) {
            LocationModel::where('id', $data->locationId)->update([
                'is_blocked' => $data->isBlocked,
                'updated_at' => now(),
            ]);

            $auditAction = $data->isBlocked ? 'location.blocked' : 'location.unblocked';
            $changeset = ['isBlocked' => $data->isBlocked];
            if ($data->reason !== null) {
                $changeset['reason'] = $data->reason;
            }

            $this->auditLogger->log(
                action: $auditAction,
                entityType: 'WarehouseLocation',
                entityId: $data->locationId,
                changeset: $changeset,
                actorId: $data->performedBy,
                correlationId: $correlationId
            );

            $eventPayload = ['locationId' => $data->locationId];
            if ($data->isBlocked && $data->reason !== null) {
                $eventPayload['reason'] = $data->reason;
            }

            EventOutboxModel::create([
                'event_id' => $outboxEventId,
                'event_type' => $eventType,
                'event_version' => 1,
                'occurred_at' => now(),
                'actor_id' => $data->performedBy,
                'correlation_id' => $correlationId,
                'causation_id' => $outboxEventId,
                'payload' => $eventPayload,
                'dispatched' => false,
            ]);
        });

        return new Location(
            id: $location->id(),
            warehouseCode: $location->warehouseCode(),
            zone: $location->zone(),
            aisle: $location->aisle(),
            rack: $location->rack(),
            level: $location->level(),
            bin: $location->bin(),
            label: $location->label(),
            isBlocked: $data->isBlocked,
        );
    }
}
