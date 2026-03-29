<?php

declare(strict_types=1);

namespace Tests\Feature\Movements;

use App\Modules\Locations\Domain\Entities\Location;
use App\Modules\Locations\Infrastructure\Persistence\Eloquent\LocationModel;
use App\Modules\Product\Infrastructure\Persistence\Eloquent\ProductModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RegisterMovementTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_register_receipt_movement(): void
    {
        $product = ProductModel::create([
            'id' => Str::uuid()->toString(),
            'sku' => 'TEST-001',
            'name' => 'Test Product',
            'category' => 'test',
            'unit_of_measure' => 'unit',
        ]);

        $location = LocationModel::create([
            'id' => Str::uuid()->toString(),
            'warehouse_code' => 'WH1',
            'zone' => 'A',
            'aisle' => '01',
            'rack' => 'R1',
            'level' => 'L1',
            'bin' => 'B1',
            'label' => 'WH1-A-01-R1-L1-B1',
            'is_blocked' => false,
        ]);

        $payload = [
            'productId' => $product->id,
            'toLocationId' => $location->id,
            'type' => 'receipt',
            'quantity' => 100,
            'reference' => 'PO-1234',
        ];

        $response = $this->postJson('/api/movements', $payload, [
            'Idempotency-Key' => Str::uuid()->toString()
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.type', 'receipt');
        $response->assertJsonPath('data.quantity', 100);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'to_location_id' => $location->id,
            'quantity' => 100,
            'type' => 'receipt',
        ]);

        $this->assertDatabaseHas('stock_items', [
            'product_id' => $product->id,
            'location_id' => $location->id,
            'quantity_on_hand' => 100,
            'quantity_available' => 100,
        ]);
        
        $this->assertDatabaseHas('event_outbox', [
            'event_type' => 'movement.created'
        ]);
        
        $this->assertDatabaseHas('event_outbox', [
            'event_type' => 'inventory.stock.received'
        ]);
    }

    public function test_optimistic_locking_conflict_returns_409(): void
    {
        $product = ProductModel::create([
            'id' => Str::uuid()->toString(),
            'sku' => 'TEST-002',
            'name' => 'Test Product 2',
            'category' => 'test',
            'unit_of_measure' => 'unit',
        ]);

        $location = LocationModel::create([
            'id' => Str::uuid()->toString(),
            'warehouse_code' => 'WH1',
            'zone' => 'A',
            'aisle' => '01',
            'rack' => 'R1',
            'level' => 'L1',
            'bin' => 'B2',
            'label' => 'WH1-A-01-R1-L1-B2',
            'is_blocked' => false,
        ]);

        $this->postJson('/api/movements', [
            'productId' => $product->id,
            'toLocationId' => $location->id,
            'type' => 'receipt',
            'quantity' => 10,
        ])->assertStatus(201);

        // Mock InternalStockMutationService to throw OptimisticLockException
        $mock = \Mockery::mock(\App\Modules\Inventory\Application\Services\InternalStockMutationService::class);
        $mock->shouldReceive('applyMovement')->andThrow(\App\Modules\Inventory\Domain\Exceptions\OptimisticLockException::forStockItem('fake_id'));
        $this->app->instance(\App\Modules\Inventory\Application\Services\InternalStockMutationService::class, $mock);

        $payload = [
            'productId' => $product->id,
            'fromLocationId' => $location->id,
            'type' => 'picking',
            'quantity' => 2,
        ];

        $response = $this->postJson('/api/movements', $payload);

        $response->assertStatus(409);
        $response->assertJsonPath('error.code', 'conflict');
    }
}
