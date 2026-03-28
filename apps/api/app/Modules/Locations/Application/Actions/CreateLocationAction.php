<?php

declare(strict_types=1);

namespace App\Modules\Locations\Application\Actions;

use App\Modules\Locations\Application\DTOs\CreateLocationData;
use App\Modules\Locations\Domain\Entities\Location;
use App\Modules\Locations\Domain\Events\LocationCreated;
use App\Modules\Locations\Domain\Exceptions\DuplicateLocationLabel;
use App\Modules\Locations\Domain\Repositories\LocationRepository;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

final class CreateLocationAction
{
    public function __construct(
        private readonly LocationRepository $locations,
    ) {
    }

    public function execute(CreateLocationData $data): Location
    {
        $label = sprintf('%s-%s-%s-%s-%s-%s',
            $data->warehouseCode,
            $data->zone,
            $data->aisle,
            $data->rack,
            $data->level,
            $data->bin
        );

        $existing = $this->locations->findByLabel($label);

        if ($existing !== null) {
            throw DuplicateLocationLabel::withLabel($label);
        }

        $location = new Location(
            id: (string) Str::uuid(),
            warehouseCode: $data->warehouseCode,
            zone: $data->zone,
            aisle: $data->aisle,
            rack: $data->rack,
            level: $data->level,
            bin: $data->bin,
            label: $label,
            isBlocked: false,
        );

        $created = $this->locations->create($location);

        Event::dispatch(new LocationCreated(
            locationId: $created->id(),
            label: $created->label(),
            warehouseCode: $created->warehouseCode(),
            occurredAt: now()->toIso8601String(),
            actorId: $data->actorId,
        ));

        return $created;
    }
}
