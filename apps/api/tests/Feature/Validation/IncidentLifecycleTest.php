<?php

declare(strict_types=1);

namespace Tests\Feature\Validation;

use App\Models\User;
use App\Modules\Incidents\Infrastructure\Persistence\Eloquent\InventoryIncidentModel;
use App\Modules\Product\Infrastructure\Persistence\Eloquent\ProductModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class IncidentLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private $operator;
    private $supervisor;
    private string $productId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operator = (new User())->forceFill([
            'id' => Str::uuid()->toString(),
            'name' => 'Operator', 'email' => 'op@test.com', 'role' => 'operator',
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
        ProductModel::create([
            'id' => $this->productId, 'sku' => 'INC-001',
            'name' => 'Incident Product', 'category' => 'test', 'unit_of_measure' => 'unit',
        ]);
    }

    public function test_full_incident_lifecycle_open_to_closed(): void
    {
        $reportResponse = $this->actingAs($this->operator)->postJson('/api/incidents', [
            'productId' => $this->productId,
            'type' => 'damage',
            'severity' => 'high',
            'description' => 'Crushed boxes found on receiving dock',
            'quantityAffected' => 5,
        ], ['Idempotency-Key' => Str::uuid()->toString()]);

        $reportResponse->assertStatus(201);
        $reportResponse->assertJsonPath('data.status', 'open');
        $reportResponse->assertJsonPath('data.reportedBy', $this->operator->id);
        $incidentId = $reportResponse->json('data.id');


        $this->assertDatabaseHas('audit_logs', [
            'action' => 'incident.reported',
            'entity_id' => $incidentId,
        ]);
        $this->assertDatabaseHas('event_outbox', [
            'event_type' => 'incident.reported',
        ]);

        $reviewResponse = $this->actingAs($this->supervisor)->patchJson("/api/incidents/{$incidentId}/status", [
            'status' => 'in_review',
        ]);
        $reviewResponse->assertStatus(200);
        $reviewResponse->assertJsonPath('data.status', 'in_review');

        $resolveResponse = $this->actingAs($this->supervisor)->patchJson("/api/incidents/{$incidentId}/status", [
            'status' => 'resolved',
        ]);
        $resolveResponse->assertStatus(200);
        $resolveResponse->assertJsonPath('data.status', 'resolved');

        $closeResponse = $this->actingAs($this->supervisor)->patchJson("/api/incidents/{$incidentId}/status", [
            'status' => 'closed',
        ]);
        $closeResponse->assertStatus(200);
        $closeResponse->assertJsonPath('data.status', 'closed');

        // All 3 status transitions must have emitted events
        $statusEvents = \Illuminate\Support\Facades\DB::table('event_outbox')
            ->where('event_type', 'incident.status.updated')->count();
        $this->assertEquals(3, $statusEvents);
    }

    public function test_invalid_transition_from_open_to_resolved_is_rejected(): void
    {
        $incidentId = Str::uuid()->toString();
        InventoryIncidentModel::create([
            'id' => $incidentId, 'product_id' => $this->productId,
            'type' => 'damage', 'severity' => 'medium', 'status' => 'open',
            'description' => 'Test', 'reported_by' => $this->operator->id,
        ]);

        $response = $this->actingAs($this->supervisor)->patchJson("/api/incidents/{$incidentId}/status", [
            'status' => 'resolved',
        ]);

        $response->assertStatus(422);
    }

    public function test_closed_incident_cannot_be_reopened(): void
    {
        $incidentId = Str::uuid()->toString();
        InventoryIncidentModel::create([
            'id' => $incidentId, 'product_id' => $this->productId,
            'type' => 'shortage', 'severity' => 'low', 'status' => 'closed',
            'description' => 'Closed test', 'reported_by' => $this->operator->id,
        ]);

        $response = $this->actingAs($this->supervisor)->patchJson("/api/incidents/{$incidentId}/status", [
            'status' => 'open',
        ]);

        $response->assertStatus(422);
    }

    public function test_incident_does_not_mutate_stock(): void
    {
        $response = $this->actingAs($this->operator)->postJson('/api/incidents', [
            'productId' => $this->productId,
            'type' => 'damage',
            'severity' => 'high',
            'description' => 'Stock should not change',
            'quantityAffected' => 10,
        ]);

        $response->assertStatus(201);

        // Domain invariant: incidents must never create stock_items records
        $stockCount = \Illuminate\Support\Facades\DB::table('stock_items')
            ->where('product_id', $this->productId)->count();
        $this->assertEquals(0, $stockCount);
    }

    public function test_incident_metadata_update_works(): void
    {
        $incidentId = Str::uuid()->toString();
        InventoryIncidentModel::create([
            'id' => $incidentId, 'product_id' => $this->productId,
            'type' => 'damage', 'severity' => 'medium', 'status' => 'open',
            'description' => 'Test', 'reported_by' => $this->operator->id,
        ]);

        $response = $this->actingAs($this->supervisor)->patchJson("/api/incidents/{$incidentId}", [
            'notes' => 'Investigated packaging line. Root cause identified.',
            'assignedTo' => $this->supervisor->id,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.notes', 'Investigated packaging line. Root cause identified.');
    }

    public function test_incident_metadata_rejects_immutable_fields(): void
    {
        $incidentId = Str::uuid()->toString();
        InventoryIncidentModel::create([
            'id' => $incidentId, 'product_id' => $this->productId,
            'type' => 'damage', 'severity' => 'medium', 'status' => 'open',
            'description' => 'Test', 'reported_by' => $this->operator->id,
        ]);

        $response = $this->actingAs($this->supervisor)->patchJson("/api/incidents/{$incidentId}", [
            'type' => 'shortage',
            'notes' => 'Trying to sneak in a type change',
        ]);

        $response->assertStatus(422);
    }
}
