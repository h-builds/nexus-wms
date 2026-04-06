<?php

declare(strict_types=1);

namespace App\Modules\Locations\Application\Actions;

use App\Modules\Events\Application\Services\EventPublisher;
use App\Modules\Locations\Application\DTOs\CreateLocationData;
use App\Modules\Locations\Domain\Entities\Location;
use App\Modules\Locations\Domain\Exceptions\DuplicateLocationLabel;
use App\Modules\Locations\Domain\Repositories\LocationRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CreateLocationAction
{
    public function __construct(
        private readonly LocationRepository $locations,
        private readonly EventPublisher $eventPublisher,
    ) {
    }

    public function execute(CreateLocationData $locationDetails): Location
    {
        $label = $this->generateLocationLabel($locationDetails);

        $this->ensureLabelIsUnique($label);

        $location = new Location(
            id: (string) Str::uuid(),
            warehouseCode: $locationDetails->warehouseCode,
            zone: $locationDetails->zone,
            aisle: $locationDetails->aisle,
            rack: $locationDetails->rack,
            level: $locationDetails->level,
            bin: $locationDetails->bin,
            label: $label,
            isBlocked: false,
        );

        $correlationId = $locationDetails->correlationId;

        return DB::transaction(function () use ($location, $locationDetails, $correlationId) {
            $persistedLocation = $this->locations->create($location);
            
            $this->dispatchLocationCreatedEvent($persistedLocation, $locationDetails->actorId, $correlationId);

            return $persistedLocation;
        });
    }

    private function generateLocationLabel(CreateLocationData $locationDetails): string
    {
        return sprintf('%s-%s-%s-%s-%s-%s',
            $locationDetails->warehouseCode,
            $locationDetails->zone,
            $locationDetails->aisle,
            $locationDetails->rack,
            $locationDetails->level,
            $locationDetails->bin
        );
    }

    private function ensureLabelIsUnique(string $label): void
    {
        $existing = $this->locations->findByLabel($label);

        if ($existing !== null) {
            throw DuplicateLocationLabel::withLabel($label);
        }
    }

    private function dispatchLocationCreatedEvent(Location $persistedLocation, ?string $actorId, string $correlationId): void
    {
        $this->eventPublisher->publish(
            eventType: 'location.created',
            payload: [
                'locationId' => $persistedLocation->id(),
                'label' => $persistedLocation->label(),
                'warehouseCode' => $persistedLocation->warehouseCode(),
            ],
            actorId: $actorId ?? 'system_user',
            correlationId: $correlationId
        );
    }
}
