<?php

declare(strict_types=1);

namespace Tests\Feature\Incidents;

use App\Modules\Incidents\Infrastructure\Persistence\Eloquent\InventoryIncidentModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class UpdateIncidentStatusTest extends TestCase
{
    use RefreshDatabase;

    private $operator;
    private $supervisor;
    private $incidentId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->operator = clone new User();
        $this->operator->setKeyType('string');
        $this->operator->setIncrementing(false);
        $this->operator->forceFill([
            'id' => Str::uuid()->toString(),
            'name' => 'Operator',
            'role' => 'operator'
        ]);

        $this->supervisor = clone new User();
        $this->supervisor->setKeyType('string');
        $this->supervisor->setIncrementing(false);
        $this->supervisor->forceFill([
            'id' => Str::uuid()->toString(),
            'name' => 'Supervisor',
            'role' => 'supervisor'
        ]);

        $this->incidentId = Str::uuid()->toString();

        InventoryIncidentModel::create([
            'id' => $this->incidentId,
            'product_id' => Str::uuid()->toString(),
            'type' => 'damage',
            'severity' => 'medium',
            'status' => 'open',
            'description' => 'Test incident',
            'reported_by' => $this->operator->id,
        ]);
    }

    public function test_operator_cannot_update_status(): void
    {
        $payload = ['status' => 'in_review'];
        
        $response = $this->actingAs($this->operator)->patchJson("/api/incidents/{$this->incidentId}/status", $payload);

        $response->assertStatus(403);
    }

    public function test_supervisor_can_update_status_and_emits_event(): void
    {
        $payload = ['status' => 'in_review'];
        
        $response = $this->actingAs($this->supervisor)->patchJson("/api/incidents/{$this->incidentId}/status", $payload);

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'in_review');

        $this->assertDatabaseHas('inventory_incidents', [
            'id' => $this->incidentId,
            'status' => 'in_review',
        ]);

        $this->assertDatabaseHas('event_outbox', [
            'event_type' => 'incident.status.updated',
            'actor_id' => $this->supervisor->id
        ]);
    }

    public function test_cannot_transition_to_invalid_status(): void
    {
        $payload = ['status' => 'resolved'];
        
        $response = $this->actingAs($this->supervisor)->patchJson("/api/incidents/{$this->incidentId}/status", $payload);

        $response->assertStatus(422);
    }

    public function test_closed_incident_cannot_be_updated(): void
    {
        InventoryIncidentModel::where('id', $this->incidentId)->update(['status' => 'closed']);

        $payload = ['status' => 'in_review'];
        
        $response = $this->actingAs($this->supervisor)->patchJson("/api/incidents/{$this->incidentId}/status", $payload);

        $response->assertStatus(422);
    }
}

