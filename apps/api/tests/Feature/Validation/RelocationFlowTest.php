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

class RelocationFlowTest extends TestCase
{
    use RefreshDatabase;

    private $operator;
    private string $productId;
    private string $locationAId;
    private string $locationBId;

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
        $this->locationAId = Str::uuid()->toString();
        $this->locationBId = Str::uuid()->toString();

        ProductModel::create([
            'id' => $this->productId, 'sku' => 'RELOC-001',
            'name' => 'Relocation Product', 'category' => 'test', 'unit_of_measure' => 'unit',
        ]);

        LocationModel::create([
            'id' => $this->locationAId, 'warehouse_code' => 'WH1',
            'zone' => 'A', 'aisle' => '01', 'rack' => 'R1', 'level' => 'L1', 'bin' => 'B1',
            'label' => 'WH1-A-01-R1-L1-B1', 'is_blocked' => false,
        ]);

        LocationModel::create([
            'id' => $this->locationBId, 'warehouse_code' => 'WH1',
            'zone' => 'B', 'aisle' => '02', 'rack' => 'R2', 'level' => 'L1', 'bin' => 'B2',
            'label' => 'WH1-B-02-R2-L1-B2', 'is_blocked' => false,
        ]);


        $this->actingAs($this->operator)->postJson('/api/movements', [
            'productId' => $this->productId,
            'toLocationId' => $this->locationAId,
            'type' => 'receipt',
            'quantity' => 100,
        ])->assertStatus(201);
    }

    public function test_relocation_transfers_stock_correctly(): void
    {
        $response = $this->actingAs($this->operator)->postJson('/api/movements', [
            'productId' => $this->productId,
            'fromLocationId' => $this->locationAId,
            'toLocationId' => $this->locationBId,
            'type' => 'relocation',
            'quantity' => 30,
        ]);

        $response->assertStatus(201);

        $sourceStock = DB::table('stock_items')
            ->where('product_id', $this->productId)
            ->where('location_id', $this->locationAId)->first();
        $this->assertEquals(70, $sourceStock->quantity_available);
        $this->assertEquals(70, $sourceStock->quantity_on_hand);

        $destStock = DB::table('stock_items')
            ->where('product_id', $this->productId)
            ->where('location_id', $this->locationBId)->first();
        $this->assertEquals(30, $destStock->quantity_available);
        $this->assertEquals(30, $destStock->quantity_on_hand);

        $this->assertDatabaseHas('event_outbox', ['event_type' => 'inventory.stock.relocated']);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'movement.registered',
            'actor_id' => $this->operator->id,
        ]);

        // Derivation invariant: quantityOnHand = quantityAvailable + quantityBlocked
        foreach ([$sourceStock, $destStock] as $stock) {
            $this->assertEquals($stock->quantity_on_hand, $stock->quantity_available + $stock->quantity_blocked);
        }
    }

    public function test_relocation_to_same_location_is_rejected(): void
    {
        $response = $this->actingAs($this->operator)->postJson('/api/movements', [
            'productId' => $this->productId,
            'fromLocationId' => $this->locationAId,
            'toLocationId' => $this->locationAId,
            'type' => 'relocation',
            'quantity' => 10,
        ]);

        $response->assertStatus(422);
    }
}
