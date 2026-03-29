<?php

declare(strict_types=1);

namespace Tests\Feature\Validation;

use App\Models\User;
use App\Modules\Locations\Infrastructure\Persistence\Eloquent\LocationModel;
use App\Modules\Product\Infrastructure\Persistence\Eloquent\ProductModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Validates the complete inbound warehouse flow:
 * create product → create locations → receipt → putaway → verify stock, events, audit
 */
class InboundFlowTest extends TestCase
{
    use RefreshDatabase;

    private $operator;
    private string $productId;
    private string $stagingLocationId;
    private string $storageLocationId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operator = (new User())->forceFill([
            'id' => Str::uuid()->toString(),
            'name' => 'Warehouse Operator',
            'email' => 'operator@warehouse.test',
            'role' => 'operator',
        ]);
        $this->operator->setKeyType('string');
        $this->operator->setIncrementing(false);

        $this->productId = Str::uuid()->toString();
        $this->stagingLocationId = Str::uuid()->toString();
        $this->storageLocationId = Str::uuid()->toString();
    }

    public function test_full_inbound_flow_receipt_then_putaway(): void
    {
        // Seed prerequisite data: product, staging location, storage location
        ProductModel::create([
            'id' => $this->productId,
            'sku' => 'INBOUND-001',
            'name' => 'Warehouse Test Product',
            'category' => 'electronics',
            'unit_of_measure' => 'unit',
        ]);


        LocationModel::create([
            'id' => $this->stagingLocationId,
            'warehouse_code' => 'WH1',
            'zone' => 'S', 'aisle' => '01', 'rack' => 'R1', 'level' => 'L1', 'bin' => 'B1',
            'label' => 'WH1-S-01-R1-L1-B1',
            'is_blocked' => false,
        ]);

        LocationModel::create([
            'id' => $this->storageLocationId,
            'warehouse_code' => 'WH1',
            'zone' => 'A', 'aisle' => '02', 'rack' => 'R3', 'level' => 'L2', 'bin' => 'B4',
            'label' => 'WH1-A-02-R3-L2-B4',
            'is_blocked' => false,
        ]);

        // Receipt: goods arrive from external PO into staging area
        $receiptResponse = $this->actingAs($this->operator)->postJson('/api/movements', [
            'productId' => $this->productId,
            'toLocationId' => $this->stagingLocationId,
            'type' => 'receipt',
            'quantity' => 50,
            'reference' => 'PO-2026-001',
            'lotNumber' => 'LOT-2026-MAR',
        ], ['Idempotency-Key' => Str::uuid()->toString()]);

        $receiptResponse->assertStatus(201);
        $receiptResponse->assertJsonPath('data.type', 'receipt');
        $receiptResponse->assertJsonPath('data.quantity', 50);
        $receiptResponse->assertJsonPath('data.performedBy', $this->operator->id);


        $this->assertDatabaseHas('stock_items', [
            'product_id' => $this->productId,
            'location_id' => $this->stagingLocationId,
            'quantity_on_hand' => 50,
            'quantity_available' => 50,
            'quantity_blocked' => 0,
        ]);


        $this->assertDatabaseHas('event_outbox', ['event_type' => 'movement.created']);
        $this->assertDatabaseHas('event_outbox', ['event_type' => 'inventory.stock.received']);


        $this->assertDatabaseHas('audit_logs', [
            'action' => 'movement.registered',
            'entity_type' => 'InventoryMovement',
            'actor_id' => $this->operator->id,
        ]);

        // Putaway: transfer from staging to designated storage
        $putawayResponse = $this->actingAs($this->operator)->postJson('/api/movements', [
            'productId' => $this->productId,
            'fromLocationId' => $this->stagingLocationId,
            'toLocationId' => $this->storageLocationId,
            'type' => 'putaway',
            'quantity' => 50,
            'lotNumber' => 'LOT-2026-MAR',
        ], ['Idempotency-Key' => Str::uuid()->toString()]);

        $putawayResponse->assertStatus(201);
        $putawayResponse->assertJsonPath('data.type', 'putaway');

        // Staging location must be fully depleted after putaway
        $stagingStock = \Illuminate\Support\Facades\DB::table('stock_items')
            ->where('product_id', $this->productId)
            ->where('location_id', $this->stagingLocationId)
            ->first();

        $this->assertEquals(0, $stagingStock->quantity_on_hand);
        $this->assertEquals(0, $stagingStock->quantity_available);

        // Storage location must hold the full quantity
        $this->assertDatabaseHas('stock_items', [
            'product_id' => $this->productId,
            'location_id' => $this->storageLocationId,
            'quantity_on_hand' => 50,
            'quantity_available' => 50,
            'quantity_blocked' => 0,
        ]);


        $this->assertDatabaseHas('event_outbox', ['event_type' => 'inventory.stock.putaway']);

        // Verify the derivation invariant holds at both locations
        $allStockItems = \Illuminate\Support\Facades\DB::table('stock_items')
            ->where('product_id', $this->productId)->get();

        foreach ($allStockItems as $item) {
            $this->assertEquals(
                $item->quantity_on_hand,
                $item->quantity_available + $item->quantity_blocked,
                "Invariant violated: quantityOnHand != quantityAvailable + quantityBlocked"
            );
        }
    }

    public function test_receipt_creates_api_compliant_response(): void
    {
        ProductModel::create([
            'id' => $this->productId,
            'sku' => 'API-CHECK-001',
            'name' => 'API Check Product',
            'category' => 'test',
            'unit_of_measure' => 'unit',
        ]);

        LocationModel::create([
            'id' => $this->stagingLocationId,
            'warehouse_code' => 'WH1',
            'zone' => 'S', 'aisle' => '01', 'rack' => 'R1', 'level' => 'L1', 'bin' => 'B1',
            'label' => 'WH1-S-01-R1-L1-B1',
            'is_blocked' => false,
        ]);

        $response = $this->actingAs($this->operator)->postJson('/api/movements', [
            'productId' => $this->productId,
            'toLocationId' => $this->stagingLocationId,
            'type' => 'receipt',
            'quantity' => 10,
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => ['id', 'productId', 'toLocationId', 'type', 'quantity', 'performedBy', 'performedAt'],
        ]);

        // Actor identity must be server-derived
        $response->assertJsonPath('data.performedBy', $this->operator->id);
    }
}
