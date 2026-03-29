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

class IdempotencyTest extends TestCase
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
            'name' => 'Op', 'email' => 'op@test.com', 'role' => 'operator',
        ]);
        $this->operator->setKeyType('string');
        $this->operator->setIncrementing(false);

        $this->productId = Str::uuid()->toString();
        $this->locationId = Str::uuid()->toString();

        ProductModel::create([
            'id' => $this->productId, 'sku' => 'IDEMP-001',
            'name' => 'Idempotency Product', 'category' => 'test', 'unit_of_measure' => 'unit',
        ]);

        LocationModel::create([
            'id' => $this->locationId, 'warehouse_code' => 'WH1',
            'zone' => 'A', 'aisle' => '01', 'rack' => 'R1', 'level' => 'L1', 'bin' => 'B1',
            'label' => 'WH1-IDEMP-01', 'is_blocked' => false,
        ]);
    }

    public function test_duplicate_movement_with_same_idempotency_key_returns_same_result(): void
    {
        $idempotencyKey = Str::uuid()->toString();

        $payload = [
            'productId' => $this->productId,
            'toLocationId' => $this->locationId,
            'type' => 'receipt',
            'quantity' => 25,
            'reference' => 'PO-IDEMP-001',
        ];

        $first = $this->actingAs($this->operator)->postJson('/api/movements', $payload, [
            'Idempotency-Key' => $idempotencyKey,
        ]);
        $first->assertStatus(201);
        $firstId = $first->json('data.id');

        $second = $this->actingAs($this->operator)->postJson('/api/movements', $payload, [
            'Idempotency-Key' => $idempotencyKey,
        ]);

        // Idempotency guarantee: same key must return identical result
        $second->assertStatus(201);
        $secondId = $second->json('data.id');
        $this->assertEquals($firstId, $secondId);

        // Only one record must exist despite two requests
        $movementCount = DB::table('inventory_movements')
            ->where('idempotency_key', $idempotencyKey)->count();
        $this->assertEquals(1, $movementCount);

        // Stock mutation must have executed exactly once
        $stock = DB::table('stock_items')
            ->where('product_id', $this->productId)
            ->where('location_id', $this->locationId)->first();
        $this->assertEquals(25, $stock->quantity_on_hand);
    }

    public function test_duplicate_incident_with_same_idempotency_key_returns_same_result(): void
    {
        $idempotencyKey = Str::uuid()->toString();

        $payload = [
            'productId' => $this->productId,
            'type' => 'damage',
            'severity' => 'medium',
            'description' => 'Idempotency test incident',
        ];

        $first = $this->actingAs($this->operator)->postJson('/api/incidents', $payload, [
            'Idempotency-Key' => $idempotencyKey,
        ]);
        $first->assertStatus(201);
        $firstId = $first->json('data.id');

        $second = $this->actingAs($this->operator)->postJson('/api/incidents', $payload, [
            'Idempotency-Key' => $idempotencyKey,
        ]);

        $second->assertStatus(201);
        $secondId = $second->json('data.id');
        $this->assertEquals($firstId, $secondId);

        // Only one record must exist despite two requests
        $incidentCount = DB::table('inventory_incidents')
            ->where('idempotency_key', $idempotencyKey)->count();
        $this->assertEquals(1, $incidentCount);
    }

    public function test_different_idempotency_keys_create_separate_records(): void
    {
        $payload = [
            'productId' => $this->productId,
            'toLocationId' => $this->locationId,
            'type' => 'receipt',
            'quantity' => 10,
        ];

        $first = $this->actingAs($this->operator)->postJson('/api/movements', $payload, [
            'Idempotency-Key' => Str::uuid()->toString(),
        ]);
        $first->assertStatus(201);

        $second = $this->actingAs($this->operator)->postJson('/api/movements', $payload, [
            'Idempotency-Key' => Str::uuid()->toString(),
        ]);
        $second->assertStatus(201);

        $this->assertNotEquals($first->json('data.id'), $second->json('data.id'));

        // Both receipts must have applied: 10 + 10 = 20
        $stock = DB::table('stock_items')
            ->where('product_id', $this->productId)
            ->where('location_id', $this->locationId)->first();
        $this->assertEquals(20, $stock->quantity_on_hand);
    }
}
