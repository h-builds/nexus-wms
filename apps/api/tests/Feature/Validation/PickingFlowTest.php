<?php

declare(strict_types=1);

namespace Tests\Feature\Validation;

use App\Models\User;
use App\Modules\Locations\Infrastructure\Persistence\Eloquent\LocationModel;
use App\Modules\Product\Infrastructure\Persistence\Eloquent\ProductModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class PickingFlowTest extends TestCase
{
    use RefreshDatabase;

    private $operator;
    private string $productId;
    private string $locationId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operator = (new User())->forceFill([
            'id' => Str::uuid()->toString(),
            'name' => 'Picker', 'email' => 'picker@test.com', 'role' => 'operator',
        ]);
        $this->operator->setKeyType('string');
        $this->operator->setIncrementing(false);

        $this->productId = Str::uuid()->toString();
        $this->locationId = Str::uuid()->toString();

        ProductModel::create([
            'id' => $this->productId, 'sku' => 'PICK-001',
            'name' => 'Pickable Product', 'category' => 'test', 'unit_of_measure' => 'unit',
        ]);

        LocationModel::create([
            'id' => $this->locationId, 'warehouse_code' => 'WH1',
            'zone' => 'A', 'aisle' => '01', 'rack' => 'R1', 'level' => 'L1', 'bin' => 'B1',
            'label' => 'WH1-A-01-R1-L1-B1', 'is_blocked' => false,
        ]);


        $this->actingAs($this->operator)->postJson('/api/movements', [
            'productId' => $this->productId,
            'toLocationId' => $this->locationId,
            'type' => 'receipt',
            'quantity' => 20,
        ])->assertStatus(201);
    }

    public function test_valid_picking_reduces_stock(): void
    {
        $response = $this->actingAs($this->operator)->postJson('/api/movements', [
            'productId' => $this->productId,
            'fromLocationId' => $this->locationId,
            'type' => 'picking',
            'quantity' => 5,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.type', 'picking');

        $stock = DB::table('stock_items')
            ->where('product_id', $this->productId)
            ->where('location_id', $this->locationId)->first();

        $this->assertEquals(15, $stock->quantity_available);
        $this->assertEquals(15, $stock->quantity_on_hand);

        $this->assertDatabaseHas('event_outbox', ['event_type' => 'inventory.stock.picked']);
    }

    public function test_insufficient_stock_picking_is_rejected(): void
    {
        $response = $this->actingAs($this->operator)->postJson('/api/movements', [
            'productId' => $this->productId,
            'fromLocationId' => $this->locationId,
            'type' => 'picking',
            'quantity' => 999,
        ]);

        $response->assertStatus(422);

        // Failed pick must not alter stock quantities
        $stock = DB::table('stock_items')
            ->where('product_id', $this->productId)
            ->where('location_id', $this->locationId)->first();

        $this->assertEquals(20, $stock->quantity_available);
        $this->assertEquals(20, $stock->quantity_on_hand);
    }

    public function test_no_negative_stock_after_sequential_picks(): void
    {
        $this->actingAs($this->operator)->postJson('/api/movements', [
            'productId' => $this->productId,
            'fromLocationId' => $this->locationId, 'type' => 'picking', 'quantity' => 15,
        ])->assertStatus(201);

        $this->actingAs($this->operator)->postJson('/api/movements', [
            'productId' => $this->productId,
            'fromLocationId' => $this->locationId, 'type' => 'picking', 'quantity' => 5,
        ])->assertStatus(201);

        // Overdraft beyond zero must be rejected
        $response = $this->actingAs($this->operator)->postJson('/api/movements', [
            'productId' => $this->productId,
            'fromLocationId' => $this->locationId, 'type' => 'picking', 'quantity' => 1,
        ]);

        $response->assertStatus(422);

        // Invariant: stock must be exactly zero, never negative
        $stock = DB::table('stock_items')
            ->where('product_id', $this->productId)
            ->where('location_id', $this->locationId)->first();

        $this->assertEquals(0, $stock->quantity_available);
        $this->assertGreaterThanOrEqual(0, $stock->quantity_available);
    }
}
