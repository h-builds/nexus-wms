<?php

declare(strict_types=1);

namespace App\Modules\Locations\Infrastructure\Http\Resources;

use App\Modules\Locations\Domain\Entities\Location;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Location
 */
final class LocationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Location $location */
        $location = $this->resource;

        return [
            'id' => $location->id(),
            'warehouseCode' => $location->warehouseCode(),
            'zone' => $location->zone(),
            'aisle' => $location->aisle(),
            'rack' => $location->rack(),
            'level' => $location->level(),
            'bin' => $location->bin(),
            'label' => $location->label(),
            'isBlocked' => $location->isBlocked(),
        ];
    }
}
