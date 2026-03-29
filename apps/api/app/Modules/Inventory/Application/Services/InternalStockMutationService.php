<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Services;

use App\Modules\Inventory\Domain\Exceptions\OptimisticLockException;
use App\Modules\Inventory\Domain\Services\InventoryValidator;
use App\Modules\Movements\Domain\Entities\InventoryMovement;
use App\Modules\Movements\Domain\Enums\MovementType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

/**
 * Service explicitly and exclusively for mutating stock levels.
 * This is an internal module API, called by Movements domain.
 */
class InternalStockMutationService
{
    public function __construct(
        private readonly InventoryValidator $validator
    ) {}

    /**
     * Applies the movement quantity to the inventory using explicit optimistic locking.
     */
    public function applyMovement(InventoryMovement $movement): void
    {
        match ($movement->type()) {
            MovementType::RECEIPT, MovementType::RETURN_INTERNAL => $this->addStock($movement->toLocationId(), $movement->productId(), $movement->quantity(), $movement->lotNumber()),
            MovementType::ADJUSTMENT, MovementType::PICKING => $this->subtractStock($movement->fromLocationId(), $movement->productId(), $movement->quantity(), $movement->lotNumber()),
            MovementType::RELOCATION, MovementType::PUTAWAY => $this->moveStock($movement->fromLocationId(), $movement->toLocationId(), $movement->productId(), $movement->quantity(), $movement->lotNumber()),
        };
    }

    private function moveStock(string $fromLocationId, string $toLocationId, string $productId, int $quantity, ?string $lotNumber): void
    {
        $this->subtractStock($fromLocationId, $productId, $quantity, $lotNumber);
        $this->addStock($toLocationId, $productId, $quantity, $lotNumber);
    }

    private function addStock(string $locationId, string $productId, int $quantity, ?string $lotNumber): void
    {
        $query = DB::table('stock_items')
            ->where('product_id', $productId)
            ->where('location_id', $locationId);

        if ($lotNumber === null) {
            $query->whereNull('lot_number');
        } else {
            $query->where('lot_number', $lotNumber);
        }

        $record = $query->first();

        if ($record) {
            $this->validator->validateAdjustment(
                currentAvailable: (int) $record->quantity_available,
                currentBlocked: (int) $record->quantity_blocked,
                deltaAvailable: $quantity,
                deltaBlocked: 0
            );

            $newAvailable = $record->quantity_available + $quantity;
            $newOnHand = $this->validator->computeOnHand($newAvailable, (int) $record->quantity_blocked);

            $affected = DB::table('stock_items')
                ->where('id', $record->id)
                ->where('version', $record->version)
                ->update([
                    'quantity_available' => $newAvailable,
                    'quantity_on_hand' => $newOnHand,
                    'version' => $record->version + 1,
                    'updated_at' => now(),
                ]);

            if ($affected === 0) {
                throw OptimisticLockException::forStockItem($record->id);
            }
        } else {
            $newAvailable = $quantity;
            $newOnHand = $this->validator->computeOnHand($newAvailable, 0);

            DB::table('stock_items')->insert([
                'id' => Str::uuid()->toString(),
                'product_id' => $productId,
                'location_id' => $locationId,
                'quantity_available' => $newAvailable,
                'quantity_on_hand' => $newOnHand,
                'quantity_blocked' => 0,
                'lot_number' => $lotNumber,
                'status' => 'available',
                'version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function subtractStock(string $locationId, string $productId, int $quantity, ?string $lotNumber): void
    {
        $query = DB::table('stock_items')
            ->where('product_id', $productId)
            ->where('location_id', $locationId);

        if ($lotNumber === null) {
            $query->whereNull('lot_number');
        } else {
            $query->where('lot_number', $lotNumber);
        }

        $record = $query->first();

        if (!$record) {
            throw new RuntimeException("Cannot subtract stock: stock item not found for product {$productId} at location {$locationId}.");
        }

        $this->validator->validateAdjustment(
            currentAvailable: (int) $record->quantity_available,
            currentBlocked: (int) $record->quantity_blocked,
            deltaAvailable: -$quantity,
            deltaBlocked: 0
        );

        $newAvailable = $record->quantity_available - $quantity;
        $newOnHand = $this->validator->computeOnHand($newAvailable, (int) $record->quantity_blocked);

        $affected = DB::table('stock_items')
            ->where('id', $record->id)
            ->where('version', $record->version)
            ->update([
                'quantity_available' => $newAvailable,
                'quantity_on_hand' => $newOnHand,
                'version' => $record->version + 1,
                'updated_at' => now(),
            ]);

        if ($affected === 0) {
            throw OptimisticLockException::forStockItem($record->id);
        }
    }
}
