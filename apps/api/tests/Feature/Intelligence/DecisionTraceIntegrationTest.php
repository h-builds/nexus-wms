<?php

declare(strict_types=1);

namespace Tests\Feature\Intelligence;

use App\Models\User;
use App\Modules\Locations\Infrastructure\Persistence\Eloquent\LocationModel;
use App\Modules\Product\Infrastructure\Persistence\Eloquent\ProductModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class DecisionTraceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private $operator;
    private $supervisor;
    private string $productId;
    private string $locationId;

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
        $this->locationId = Str::uuid()->toString();

        ProductModel::create([
            'id' => $this->productId, 'sku' => 'TEST-PROD-01',
            'name' => 'Test Product', 'category' => 'test', 'unit_of_measure' => 'unit',
        ]);

        LocationModel::create([
            'id' => $this->locationId, 'warehouse_code' => 'WH1',
            'zone' => 'A', 'aisle' => '01', 'rack' => 'R1', 'level' => 'L1', 'bin' => 'B1',
            'label' => 'WH1-A-01', 'is_blocked' => false,
        ]);

        // Place initial stock: 100 units
        $this->actingAs($this->operator)->postJson('/api/movements', [
            'productId' => $this->productId,
            'toLocationId' => $this->locationId,
            'type' => 'receipt',
            'quantity' => 100,
        ])->assertStatus(201);
    }

    public function test_decision_trace_lifecycle(): void
    {
        // 1. Trigger stock adjustment (>= 30% drop to trigger InventoryAnomalyAgent)
        $this->actingAs($this->operator)->postJson('/api/movements', [
            'productId' => $this->productId,
            'fromLocationId' => $this->locationId,
            'type' => 'adjustment',
            'quantity' => 35,
            'reason' => 'cycle_count',
        ])->assertStatus(201);

        // 2. Ensure the agent ran and created a trace
        $trace = DB::table('decision_traces')->first();
        $this->assertNotNull($trace, 'Agent should have created a decision trace for >=30% adjustment.');
        $this->assertEquals('advisory', $trace->status);
        $this->assertEquals('inventory', $trace->agent_domain);
        $this->assertEquals('inventory-anomaly-agent', $trace->agent_id);

        $traceId = $trace->id;

        // 3. GET /api/intelligence/decision-traces
        $response = $this->actingAs($this->supervisor)->getJson('/api/intelligence/decision-traces');
        $response->assertStatus(200);
        $response->assertJsonPath('data.0.id', $traceId);

        // 4. Acknowledge
        $this->actingAs($this->supervisor)->patchJson("/api/intelligence/decision-traces/{$traceId}/acknowledge")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'acknowledged');

        // 5. Act Upon
        $this->actingAs($this->supervisor)->patchJson("/api/intelligence/decision-traces/{$traceId}/act-upon", [
            'actor_id' => $this->supervisor->id,
        ])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'acted_upon');

        // 6. Invalid transition (acted_upon -> dismissed should fail)
        $this->actingAs($this->supervisor)->patchJson("/api/intelligence/decision-traces/{$traceId}/dismiss", [
            'actor_id' => $this->supervisor->id,
        ])
            ->assertStatus(422);
    }

    public function test_decision_trace_query_and_metrics(): void
    {
        // We have 100 units initially.
        // Adjustment 1: Drop by 35 (35%). Triggers trace 1. Leaves 65.
        $this->actingAs($this->operator)->postJson('/api/movements', [
            'productId' => $this->productId,
            'fromLocationId' => $this->locationId,
            'type' => 'adjustment',
            'quantity' => 35,
            'reason' => 'cycle_count',
        ])->assertStatus(201);

        // Adjustment 2: Drop by 25 (38% of 65). Triggers trace 2. Leaves 40.
        $this->actingAs($this->operator)->postJson('/api/movements', [
            'productId' => $this->productId,
            'fromLocationId' => $this->locationId,
            'type' => 'adjustment',
            'quantity' => 25,
            'reason' => 'cycle_count',
        ])->assertStatus(201);

        // Adjustment 3: Drop by 20 (50% of 40). Triggers trace 3. Leaves 20.
        $this->actingAs($this->operator)->postJson('/api/movements', [
            'productId' => $this->productId,
            'fromLocationId' => $this->locationId,
            'type' => 'adjustment',
            'quantity' => 20,
            'reason' => 'cycle_count',
        ])->assertStatus(201);

        $tracesCount = DB::table('decision_traces')->count();
        $this->assertEquals(3, $tracesCount);

        $response = $this->actingAs($this->supervisor)->getJson('/api/intelligence/decision-traces?status=advisory');
        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));

        $metrics = $this->actingAs($this->supervisor)->getJson('/api/intelligence/decision-traces/metrics');
        $metrics->assertStatus(200);
        $this->assertEquals(3, $metrics->json('metrics.advisoryCount'));
    }

    public function test_agent_duplicate_prevention(): void
    {
        // Issue an adjustment that triggers the agent
        $this->actingAs($this->operator)->postJson('/api/movements', [
            'productId' => $this->productId,
            'fromLocationId' => $this->locationId,
            'type' => 'adjustment',
            'quantity' => 50,
            'reason' => 'cycle_count',
        ])->assertStatus(201);

        $tracesCount = DB::table('decision_traces')->count();
        $this->assertEquals(1, $tracesCount);

        // Manually run the AgentExecutor on the exact same event that's in the outbox
        $event = DB::table('event_outbox')->where('event_type', 'inventory.stock.adjusted')->first();
        
        $executor = $this->app->make(\App\Modules\Intelligence\Application\Agents\AgentExecutor::class);
        $canonicalEvent = new \App\Modules\Intelligence\Application\Agents\CanonicalEvent(
            eventId: $event->event_id,
            eventType: $event->event_type,
            eventVersion: 1,
            occurredAt: now()->toIso8601String(),
            actorId: 'system',
            correlationId: $event->correlation_id,
            causationId: '', // The outbox payload usually doesn't have causationId, so EvaluateOutboxEventAction defaults it to ''
            payload: json_decode($event->payload, true),
        );
        $executor->evaluate($canonicalEvent);

        // Count should still be 1 due to idempotency by causationId+agentId
        $tracesCountAfter = DB::table('decision_traces')->count();
        $this->assertEquals(1, $tracesCountAfter, 'Duplicate trace was created for the same event.');
    }

    public function test_agent_failure_isolation(): void
    {
        $mockAgent = \Mockery::mock(\App\Modules\Intelligence\Application\Agents\DecisionAgent::class);
        $mockAgent->shouldReceive('evaluate')->andThrow(new \RuntimeException('Agent crashed'));
        
        $executor = new \App\Modules\Intelligence\Application\Agents\AgentExecutor(
            [$mockAgent],
            $this->app->make(\App\Modules\Intelligence\Domain\Repositories\DecisionTraceRepository::class)
        );
        
        $this->app->instance(\App\Modules\Intelligence\Application\Agents\AgentExecutor::class, $executor);

        // Emit a valid outbox event through the movement API
        $response = $this->actingAs($this->operator)->postJson('/api/movements', [
            'productId' => $this->productId,
            'fromLocationId' => $this->locationId,
            'type' => 'adjustment',
            'quantity' => 50,
            'reason' => 'cycle_count',
        ]);
        
        // Ensure the API still returns 201 Created and the movement pipeline completed despite the agent crash
        $response->assertStatus(201);
        
        // Assert no DecisionTrace is created
        $tracesCount = DB::table('decision_traces')->count();
        $this->assertEquals(0, $tracesCount);
    }
}
