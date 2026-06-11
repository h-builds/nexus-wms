<?php

declare(strict_types=1);

namespace Tests\Feature\Events;

use App\Models\User;
use App\Modules\Locations\Infrastructure\Persistence\Eloquent\LocationModel;
use App\Modules\Product\Infrastructure\Persistence\Eloquent\ProductModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class CrossSurfaceEventContractTest extends TestCase
{
    use RefreshDatabase;

    private $operator;
    private string $productId;
    private string $locationId;
    private string $toLocationId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operator = (new User())->forceFill([
            'id' => Str::uuid()->toString(),
            'name' => 'Operator', 'email' => 'op@test.com', 'role' => 'operator',
        ]);
        $this->operator->setKeyType('string');
        $this->operator->setIncrementing(false);

        $this->productId = Str::uuid()->toString();
        $this->locationId = Str::uuid()->toString();
        $this->toLocationId = Str::uuid()->toString();
    }

    public function test_product_created_event_contract(): void
    {
        $payload = [
            'sku' => 'TEST-SKU-001',
            'name' => 'Test Product',
            'category' => 'electronics',
            'unitOfMeasure' => 'unit',
            'attributes' => ['color' => 'red']
        ];

        $this->actingAs($this->operator)->postJson('/api/products', $payload)->assertStatus(201);

        $event = DB::table('event_outbox')->where('event_type', 'product.created')->first();
        $this->assertNotNull($event);

        $data = json_decode($event->payload, true);
        $this->assertArrayHasKey('productId', $data);
        $this->assertArrayHasKey('sku', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('category', $data);
        $this->assertArrayHasKey('unitOfMeasure', $data);
        // Note: EVENT_CATALOG.md doesn't mention attributes, they shouldn't be leaked or it's fine if they aren't there.
        $this->assertArrayNotHasKey('attributes', $data);
    }

    public function test_location_events_contract(): void
    {
        // 1. location.created
        $payload = [
            'warehouseCode' => 'WH1',
            'zone' => 'A',
            'aisle' => '01',
            'rack' => 'R1',
            'level' => 'L1',
            'bin' => 'B1',
            'label' => 'WH1-A-01-R1-L1-B1'
        ];

        $response = $this->actingAs($this->operator)->postJson('/api/locations', $payload);
        $response->assertStatus(201);
        $locId = $response->json('data.id');

        $createdEvent = DB::table('event_outbox')->where('event_type', 'location.created')->first();
        $data = json_decode($createdEvent->payload, true);
        $this->assertArrayHasKey('locationId', $data);
        $this->assertArrayHasKey('label', $data);
        $this->assertArrayHasKey('warehouseCode', $data);

        // 2. location.blocked
        $supervisor = (new User())->forceFill([
            'id' => Str::uuid()->toString(),
            'name' => 'Supervisor', 'email' => 'sup@test.com', 'role' => 'supervisor',
        ]);
        $this->actingAs($supervisor)->patchJson("/api/locations/{$locId}/status", [
            'isBlocked' => true,
            'reason' => 'maintenance'
        ])->assertStatus(200);

        $blockedEvent = DB::table('event_outbox')->where('event_type', 'location.blocked')->first();
        $data = json_decode($blockedEvent->payload, true);
        $this->assertArrayHasKey('locationId', $data);
        $this->assertArrayHasKey('reason', $data);

        // 3. location.unblocked
        $this->actingAs($supervisor)->patchJson("/api/locations/{$locId}/status", [
            'isBlocked' => false
        ])->assertStatus(200);

        $unblockedEvent = DB::table('event_outbox')->where('event_type', 'location.unblocked')->first();
        $data = json_decode($unblockedEvent->payload, true);
        $this->assertArrayHasKey('locationId', $data);
        $this->assertArrayNotHasKey('reason', $data);
    }

    public function test_inventory_and_movement_events_contract(): void
    {
        ProductModel::forceCreate([
            'id' => $this->productId,
            'sku' => 'M-SKU-001',
            'name' => 'Moveable',
            'category' => 'other',
            'unit_of_measure' => 'unit',
            'attributes' => '[]',
        ]);
        LocationModel::forceCreate([
            'id' => $this->locationId,
            'warehouse_code' => 'WH1',
            'label' => 'LOC1',
            'is_blocked' => false,
            'zone' => 'A',
            'aisle' => '1',
            'rack' => '1',
            'level' => '1',
            'bin' => '1',
        ]);
        LocationModel::forceCreate([
            'id' => $this->toLocationId,
            'warehouse_code' => 'WH1',
            'label' => 'LOC2',
            'is_blocked' => false,
            'zone' => 'A',
            'aisle' => '1',
            'rack' => '1',
            'level' => '1',
            'bin' => '2',
        ]);

        // 0. Receipt (so we have stock to relocate)
        $this->actingAs($this->operator)->postJson('/api/movements', [
            'type' => 'receipt',
            'productId' => $this->productId,
            'toLocationId' => $this->locationId,
            'quantity' => 100,
            'reference' => 'PO-12345',
        ])->assertStatus(201);

        // 1. Relocation
        $response = $this->actingAs($this->operator)->postJson('/api/movements', [
            'type' => 'relocation',
            'productId' => $this->productId,
            'fromLocationId' => $this->locationId,
            'toLocationId' => $this->toLocationId,
            'quantity' => 10,
        ]);
        $response->assertStatus(201);
        
        $movementEvent = DB::table('event_outbox')->where('event_type', 'movement.created')->first();
        $movementData = json_decode($movementEvent->payload, true);
        $this->assertArrayHasKey('movementId', $movementData);
        $this->assertArrayHasKey('productId', $movementData);
        $this->assertArrayHasKey('type', $movementData);
        $this->assertArrayHasKey('quantity', $movementData);
        $this->assertArrayHasKey('fromLocationId', $movementData);
        $this->assertArrayHasKey('toLocationId', $movementData);

        $inventoryEvent = DB::table('event_outbox')->where('event_type', 'inventory.stock.relocated')->first();
        $inventoryData = json_decode($inventoryEvent->payload, true);
        $this->assertArrayHasKey('productId', $inventoryData);
        $this->assertArrayHasKey('fromLocationId', $inventoryData);
        $this->assertArrayHasKey('toLocationId', $inventoryData);
        $this->assertArrayHasKey('quantity', $inventoryData);

        // 2. Adjustment
        $this->actingAs($this->operator)->postJson('/api/movements', [
            'type' => 'adjustment',
            'productId' => $this->productId,
            'fromLocationId' => $this->locationId,
            'quantity' => 5,
            'reason' => 'cycle_count',
        ])->assertStatus(201);

        $adjEvent = DB::table('event_outbox')->where('event_type', 'inventory.stock.adjusted')->first();
        $adjData = json_decode($adjEvent->payload, true);
        $this->assertArrayHasKey('productId', $adjData);
        $this->assertArrayHasKey('locationId', $adjData);
        $this->assertArrayHasKey('previousQuantity', $adjData);
        $this->assertArrayHasKey('newQuantity', $adjData);
        $this->assertArrayHasKey('reason', $adjData);
    }
}
