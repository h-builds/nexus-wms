<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Infrastructure\Http\Resources;

use App\Modules\Inventory\Domain\Entities\StockItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin StockItem
 */
final class StockItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var StockItem $stockItem */
        $stockItem = $this->resource;

        return [
            'id' => $stockItem->id(),
            'productId' => $stockItem->productId(),
            'locationId' => $stockItem->locationId(),
            'quantityOnHand' => $stockItem->quantityOnHand(),
            'quantityAvailable' => $stockItem->quantityAvailable(),
            'quantityBlocked' => $stockItem->quantityBlocked(),
            'lotNumber' => $stockItem->lotNumber(),
            'serialNumber' => $stockItem->serialNumber(),
            'receivedAt' => $stockItem->receivedAt(),
            'expiresAt' => $stockItem->expiresAt(),
            'status' => $stockItem->status()->value,
        ];
    }
}
