<?php

declare(strict_types=1);

namespace App\Modules\Movements\Infrastructure\Http\Resources;

use App\Modules\Movements\Domain\Entities\InventoryMovement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class MovementResource extends JsonResource
{
    /**
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var InventoryMovement $movement */
        $movement = $this->resource;

        return [
            'id' => $movement->id(),
            'productId' => $movement->productId(),
            'fromLocationId' => $movement->fromLocationId(),
            'toLocationId' => $movement->toLocationId(),
            'type' => $movement->type()->value,
            'quantity' => $movement->quantity(),
            'reference' => $movement->reference(),
            'lotNumber' => $movement->lotNumber(),
            'reason' => $movement->reason(),
            'performedBy' => $movement->performedBy(),
            'performedAt' => $movement->performedAt(),
        ];
    }
}
