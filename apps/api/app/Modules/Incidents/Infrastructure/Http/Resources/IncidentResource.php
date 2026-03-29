<?php

declare(strict_types=1);

namespace App\Modules\Incidents\Infrastructure\Http\Resources;

use App\Modules\Incidents\Domain\Entities\InventoryIncident;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class IncidentResource extends JsonResource
{
    public function toArray($request): array
    {
        $incident = $this->resource;

        return [
            'id' => $incident->id(),
            'productId' => $incident->productId(),
            'locationId' => $incident->locationId(),
            'type' => $incident->type()->value,
            'severity' => $incident->severity()->value,
            'description' => $incident->description(),
            'quantityAffected' => $incident->quantityAffected(),
            'status' => $incident->status()->value,
            'reportedBy' => $incident->reportedBy(),
            'assignedTo' => $incident->assignedTo(),
            'notes' => $incident->notes(),
            'createdAt' => $incident->createdAt(),
            'updatedAt' => $incident->updatedAt(),
        ];
    }
}
