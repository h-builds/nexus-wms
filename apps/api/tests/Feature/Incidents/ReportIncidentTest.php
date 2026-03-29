<?php

declare(strict_types=1);

namespace Tests\Feature\Incidents;

use App\Modules\Locations\Infrastructure\Persistence\Eloquent\LocationModel;
use App\Modules\Product\Infrastructure\Persistence\Eloquent\ProductModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReportIncidentTest extends TestCase
{
    use RefreshDatabase;

    private $operator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->operator = (new User())->forceFill([
            'id' => Str::uuid()->toString(),
            'name' => 'Operator User',
            'email' => 'operator@test.com',
            'role' => 'operator'
        ]);
        $this->operator->setAttribute($this->operator->getKeyName(), Str::uuid()->toString());
        $this->operator->setKeyType('string');
        $this->operator->setIncrementing(false);
    }

    public function test_can_report_incident(): void
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
            'locationId' => $location->id,
            'type' => 'damage',
            'severity' => 'high',
            'description' => 'Product was crushed by forklift',
            'quantityAffected' => 10,
        ];

        $response = $this->actingAs($this->operator)->postJson('/api/incidents', $payload, [
            'Idempotency-Key' => Str::uuid()->toString()
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.type', 'damage');
        $response->assertJsonPath('data.status', 'open');
        $response->assertJsonPath('data.reportedBy', $this->operator->id);

        $this->assertDatabaseHas('inventory_incidents', [
            'product_id' => $product->id,
            'location_id' => $location->id,
            'quantity_affected' => 10,
            'type' => 'damage',
            'status' => 'open',
            'reported_by' => $this->operator->id,
        ]);
        
        $this->assertDatabaseHas('event_outbox', [
            'event_type' => 'incident.reported',
            'actor_id' => $this->operator->id
        ]);
    }

    public function test_fails_on_invalid_type(): void
    {
        $product = ProductModel::create([
            'id' => Str::uuid()->toString(),
            'sku' => 'TEST-002',
            'name' => 'Test Product',
            'category' => 'test',
            'unit_of_measure' => 'unit',
        ]);

        $payload = [
            'productId' => $product->id,
            'type' => 'alien_abduction',
            'severity' => 'high',
            'description' => 'Missing',
        ];

        $response = $this->actingAs($this->operator)->postJson('/api/incidents', $payload);

        $response->assertStatus(422);
    }

    public function test_quantity_affected_can_exceed_available_without_blocking_creation(): void
    {
        $product = ProductModel::create([
            'id' => Str::uuid()->toString(),
            'sku' => 'TEST-INF-QTY',
            'name' => 'Test Product Qty',
            'category' => 'test',
            'unit_of_measure' => 'unit',
        ]);

        $payload = [
            'productId' => $product->id,
            'type' => 'shortage',
            'severity' => 'medium',
            'description' => 'Reported 100 missing units',
            'quantityAffected' => 100,
        ];

        $response = $this->actingAs($this->operator)->postJson('/api/incidents', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('inventory_incidents', [
            'product_id' => $product->id,
            'quantity_affected' => 100,
            'type' => 'shortage'
        ]);
    }
}
