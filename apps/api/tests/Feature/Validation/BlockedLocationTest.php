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

class BlockedLocationTest extends TestCase
{
    use RefreshDatabase;

    private $operator;
    private $supervisor;
    private string $productId;
    private string $blockedLocationId;
    private string $openLocationId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operator = (new User())->forceFill([
            'id' => Str::uuid()->toString(),
            'name' => 'Op', 'email' => 'op@test.com', 'role' => 'operator',
        ]);
        $this->operator->setKeyType('string');
        $this->operator->setIncrementing(false);

        $this->supervisor = (new User())->forceFill([
            'id' => Str::uuid()->toString(),
            'name' => 'Supervisor', 'email' => 'sup@test.com', 'role' => 'supervisor',
        ]);
        $this->supervisor->setKeyType('string');
        $this->supervisor->setIncrementing(false);

        $this->productId = Str::uuid()->toString();
        $this->blockedLocationId = Str::uuid()->toString();
        $this->openLocationId = Str::uuid()->toString();

        ProductModel::create([
            'id' => $this->productId, 'sku' => 'BLOCK-001',
            'name' => 'Blocked Test Product', 'category' => 'test', 'unit_of_measure' => 'unit',
        ]);

        LocationModel::create([
            'id' => $this->openLocationId, 'warehouse_code' => 'WH1',
            'zone' => 'A', 'aisle' => '01', 'rack' => 'R1', 'level' => 'L1', 'bin' => 'B1',
            'label' => 'WH1-OPEN-01', 'is_blocked' => false,
        ]);

        LocationModel::create([
            'id' => $this->blockedLocationId, 'warehouse_code' => 'WH1',
            'zone' => 'B', 'aisle' => '02', 'rack' => 'R2', 'level' => 'L1', 'bin' => 'B2',
            'label' => 'WH1-BLOCKED-01', 'is_blocked' => false,
        ]);


        $this->actingAs($this->operator)->postJson('/api/movements', [
            'productId' => $this->productId,
            'toLocationId' => $this->openLocationId,
            'type' => 'receipt',
            'quantity' => 50,
        ])->assertStatus(201);
    }

    public function test_block_location_via_endpoint(): void
    {
        $response = $this->actingAs($this->supervisor)->patchJson("/api/locations/{$this->blockedLocationId}/status", [
            'isBlocked' => true,
            'reason' => 'Contamination risk',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.isBlocked', true);

        $this->assertDatabaseHas('locations', [
            'id' => $this->blockedLocationId,
            'is_blocked' => true,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'location.blocked',
            'entity_id' => $this->blockedLocationId,
        ]);

        $this->assertDatabaseHas('event_outbox', [
            'event_type' => 'location.blocked',
        ]);
    }

    public function test_receipt_to_blocked_location_is_rejected(): void
    {
        $this->actingAs($this->supervisor)->patchJson("/api/locations/{$this->blockedLocationId}/status", [
            'isBlocked' => true,
        ])->assertStatus(200);

        $response = $this->actingAs($this->operator)->postJson('/api/movements', [
            'productId' => $this->productId,
            'toLocationId' => $this->blockedLocationId,
            'type' => 'receipt',
            'quantity' => 10,
        ]);

        $response->assertStatus(422);
    }

    public function test_relocation_to_blocked_destination_is_rejected(): void
    {
        $this->actingAs($this->supervisor)->patchJson("/api/locations/{$this->blockedLocationId}/status", [
            'isBlocked' => true,
        ])->assertStatus(200);

        $response = $this->actingAs($this->operator)->postJson('/api/movements', [
            'productId' => $this->productId,
            'fromLocationId' => $this->openLocationId,
            'toLocationId' => $this->blockedLocationId,
            'type' => 'relocation',
            'quantity' => 5,
        ]);

        $response->assertStatus(422);
    }

    public function test_picking_from_blocked_source_is_rejected(): void
    {
        // Stock must exist at this location before it can be blocked for picking validation
        $this->actingAs($this->operator)->postJson('/api/movements', [
            'productId' => $this->productId,
            'toLocationId' => $this->blockedLocationId,
            'type' => 'receipt',
            'quantity' => 20,
        ])->assertStatus(201);

        $this->actingAs($this->supervisor)->patchJson("/api/locations/{$this->blockedLocationId}/status", [
            'isBlocked' => true,
        ])->assertStatus(200);

        $response = $this->actingAs($this->operator)->postJson('/api/movements', [
            'productId' => $this->productId,
            'fromLocationId' => $this->blockedLocationId,
            'type' => 'picking',
            'quantity' => 5,
        ]);

        $response->assertStatus(422);
    }

    public function test_unblock_location_restores_movement_capability(): void
    {
        $this->actingAs($this->supervisor)->patchJson("/api/locations/{$this->blockedLocationId}/status", [
            'isBlocked' => true,
        ])->assertStatus(200);

        $blockedResponse = $this->actingAs($this->operator)->postJson('/api/movements', [
            'productId' => $this->productId,
            'toLocationId' => $this->blockedLocationId,
            'type' => 'receipt',
            'quantity' => 5,
        ]);
        $blockedResponse->assertStatus(422);

        $this->actingAs($this->supervisor)->patchJson("/api/locations/{$this->blockedLocationId}/status", [
            'isBlocked' => false,
        ])->assertStatus(200);

        $this->assertDatabaseHas('event_outbox', ['event_type' => 'location.unblocked']);

        // After unblock, movements to this location must succeed again
        $unblockedResponse = $this->actingAs($this->operator)->postJson('/api/movements', [
            'productId' => $this->productId,
            'toLocationId' => $this->blockedLocationId,
            'type' => 'receipt',
            'quantity' => 5,
        ]);
        $unblockedResponse->assertStatus(201);
    }

    public function test_operator_cannot_block_location(): void
    {
        $response = $this->actingAs($this->operator)->patchJson("/api/locations/{$this->blockedLocationId}/status", [
            'isBlocked' => true,
        ]);

        $response->assertStatus(403);
    }
}
