<?php

declare(strict_types=1);

namespace Tests\Feature\Validation;

use App\Models\User;
use App\Modules\Incidents\Infrastructure\Persistence\Eloquent\InventoryIncidentModel;
use App\Modules\Locations\Infrastructure\Persistence\Eloquent\LocationModel;
use App\Modules\Product\Infrastructure\Persistence\Eloquent\ProductModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApiContractTest extends TestCase
{
    use RefreshDatabase;

    private $operator;
    private $supervisor;

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
    }

    // --- Pagination ---

    public function test_products_list_returns_paginated_response(): void
    {
        ProductModel::create([
            'id' => Str::uuid()->toString(), 'sku' => 'PAG-001',
            'name' => 'Paginated Product', 'category' => 'test', 'unit_of_measure' => 'unit',
        ]);

        $response = $this->getJson('/api/products');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'meta' => ['currentPage', 'perPage', 'totalItems', 'totalPages'],
        ]);
    }

    public function test_locations_list_returns_paginated_response(): void
    {
        LocationModel::create([
            'id' => Str::uuid()->toString(), 'warehouse_code' => 'WH1',
            'zone' => 'A', 'aisle' => '01', 'rack' => 'R1', 'level' => 'L1', 'bin' => 'B1',
            'label' => 'WH1-PAG-01', 'is_blocked' => false,
        ]);

        $response = $this->getJson('/api/locations');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'meta' => ['currentPage', 'perPage', 'totalItems', 'totalPages'],
        ]);
    }

    public function test_movements_list_returns_paginated_response(): void
    {
        $response = $this->getJson('/api/movements');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'meta' => ['currentPage', 'perPage', 'totalItems', 'totalPages'],
        ]);
    }

    public function test_incidents_list_returns_paginated_response(): void
    {
        $response = $this->getJson('/api/incidents');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'meta' => ['currentPage', 'perPage', 'totalItems', 'totalPages'],
        ]);
    }

    public function test_inventory_list_returns_paginated_response(): void
    {
        $response = $this->getJson('/api/inventory');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'meta' => ['currentPage', 'perPage', 'totalItems', 'totalPages'],
        ]);
    }

    // --- Success envelopes ---

    public function test_single_resource_wrapped_in_data_envelope(): void
    {
        $productId = Str::uuid()->toString();
        ProductModel::create([
            'id' => $productId, 'sku' => 'ENV-001',
            'name' => 'Envelope Product', 'category' => 'test', 'unit_of_measure' => 'unit',
        ]);

        $response = $this->getJson("/api/products/{$productId}");
        $response->assertStatus(200);
        $response->assertJsonStructure(['data' => ['id', 'sku', 'name']]);
    }

    // --- Error envelopes ---

    public function test_validation_error_returns_422_with_error_envelope(): void
    {
        $response = $this->actingAs($this->operator)->postJson('/api/movements', [
            'type' => 'receipt',
            'quantity' => -1,
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'error' => ['code', 'message', 'details'],
        ]);
        $response->assertJsonPath('error.code', 'validation_failed');
    }

    public function test_not_found_returns_404_with_error_envelope(): void
    {
        $fakeId = Str::uuid()->toString();
        $response = $this->getJson("/api/movements/{$fakeId}");

        $response->assertStatus(404);
        $response->assertJsonStructure(['error' => ['code', 'message']]);
        $response->assertJsonPath('error.code', 'not_found');
    }

    public function test_forbidden_returns_403_with_error_envelope(): void
    {
        $incidentId = Str::uuid()->toString();
        InventoryIncidentModel::create([
            'id' => $incidentId,
            'product_id' => Str::uuid()->toString(),
            'type' => 'damage', 'severity' => 'medium', 'status' => 'open',
            'description' => 'RBAC test', 'reported_by' => $this->operator->id,
        ]);

        // RBAC: only supervisor/admin can change incident status
        $response = $this->actingAs($this->operator)->patchJson("/api/incidents/{$incidentId}/status", [
            'status' => 'in_review',
        ]);

        $response->assertStatus(403);
    }

    // --- Actor identity ---

    public function test_actor_identity_is_server_derived_not_client_provided(): void
    {
        $productId = Str::uuid()->toString();
        $locationId = Str::uuid()->toString();

        ProductModel::create([
            'id' => $productId, 'sku' => 'ACTOR-001',
            'name' => 'Actor Test', 'category' => 'test', 'unit_of_measure' => 'unit',
        ]);

        LocationModel::create([
            'id' => $locationId, 'warehouse_code' => 'WH1',
            'zone' => 'A', 'aisle' => '01', 'rack' => 'R1', 'level' => 'L1', 'bin' => 'B1',
            'label' => 'WH1-ACTOR-01', 'is_blocked' => false,
        ]);

        // Even if the client tried to set performedBy in the body (which isn't a valid field),
        // the server must extract actor from the session
        $response = $this->actingAs($this->operator)->postJson('/api/movements', [
            'productId' => $productId,
            'toLocationId' => $locationId,
            'type' => 'receipt',
            'quantity' => 10,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.performedBy', $this->operator->id);

        // Audit trail must use server-resolved actor, not any client payload
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $this->operator->id,
            'action' => 'movement.registered',
        ]);
    }

    // --- camelCase response fields ---

    public function test_movement_response_uses_camelcase(): void
    {
        $productId = Str::uuid()->toString();
        $locationId = Str::uuid()->toString();

        ProductModel::create([
            'id' => $productId, 'sku' => 'CAMEL-001',
            'name' => 'CamelCase Product', 'category' => 'test', 'unit_of_measure' => 'unit',
        ]);

        LocationModel::create([
            'id' => $locationId, 'warehouse_code' => 'WH1',
            'zone' => 'A', 'aisle' => '01', 'rack' => 'R1', 'level' => 'L1', 'bin' => 'B1',
            'label' => 'WH1-CAMEL-01', 'is_blocked' => false,
        ]);

        $response = $this->actingAs($this->operator)->postJson('/api/movements', [
            'productId' => $productId,
            'toLocationId' => $locationId,
            'type' => 'receipt',
            'quantity' => 5,
        ]);

        $response->assertStatus(201);
        $json = $response->json('data');
        $this->assertArrayHasKey('productId', $json);
        $this->assertArrayHasKey('toLocationId', $json);
        $this->assertArrayHasKey('performedBy', $json);
        $this->assertArrayHasKey('performedAt', $json);
        $this->assertArrayNotHasKey('product_id', $json);
        $this->assertArrayNotHasKey('performed_by', $json);
    }
}
