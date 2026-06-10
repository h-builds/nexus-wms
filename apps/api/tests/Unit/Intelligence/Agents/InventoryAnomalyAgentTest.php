<?php

declare(strict_types=1);

namespace Tests\Unit\Intelligence\Agents;

use App\Modules\Intelligence\Application\Agents\CanonicalEvent;
use App\Modules\Intelligence\Application\Agents\InventoryAnomalyAgent;
use App\Modules\Intelligence\Domain\Enums\AgentDomain;
use App\Modules\Intelligence\Domain\Enums\TraceSeverity;
use App\Modules\Intelligence\Domain\Enums\TraceStatus;
use App\Modules\Intelligence\Domain\Enums\TraceType;
use PHPUnit\Framework\TestCase;

final class InventoryAnomalyAgentTest extends TestCase
{
    private InventoryAnomalyAgent $agent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agent = new InventoryAnomalyAgent();
    }

    public function test_ignores_non_adjustment_events(): void
    {
        $canonicalEvent = $this->makeEvent('movement.created', [
            'movementId' => 'mov_001',
            'productId' => 'prod_001',
        ]);

        $anomalyTrace = $this->agent->evaluate($canonicalEvent);

        $this->assertNull($anomalyTrace);
    }

    public function test_ignores_small_adjustments_below_threshold(): void
    {
        $canonicalEvent = $this->makeEvent('inventory.stock.adjusted', [
            'productId' => 'prod_001',
            'locationId' => 'loc_001',
            'previousQuantity' => 100,
            'newQuantity' => 80,
            'reason' => 'manual_adjustment',
        ]);

        $anomalyTrace = $this->agent->evaluate($canonicalEvent);

        $this->assertNull($anomalyTrace, 'A 20% change should NOT trigger detection (threshold is 30%).');
    }

    public function test_detects_adjustment_at_threshold(): void
    {
        $canonicalEvent = $this->makeEvent('inventory.stock.adjusted', [
            'productId' => 'prod_001',
            'locationId' => 'loc_001',
            'previousQuantity' => 100,
            'newQuantity' => 70,
            'reason' => 'manual_adjustment',
        ]);

        $anomalyTrace = $this->agent->evaluate($canonicalEvent);

        $this->assertNotNull($anomalyTrace, 'A 30% change should trigger detection.');
        $this->assertSame(TraceType::AnomalyDetection, $anomalyTrace->traceType());
        $this->assertSame(AgentDomain::Inventory, $anomalyTrace->agentDomain());
        $this->assertSame('inventory-anomaly-agent', $anomalyTrace->agentId());
        $this->assertSame(TraceSeverity::Medium, $anomalyTrace->severity());
        $this->assertSame(TraceStatus::Advisory, $anomalyTrace->status());
        $this->assertSame('evt_001', $anomalyTrace->causationId());
        $this->assertSame('corr_001', $anomalyTrace->correlationId());
        $this->assertSame(['evt_001'], $anomalyTrace->triggerEventIds());
    }

    public function test_detects_large_adjustment_with_high_severity(): void
    {
        $canonicalEvent = $this->makeEvent('inventory.stock.adjusted', [
            'productId' => 'prod_002',
            'locationId' => 'loc_002',
            'previousQuantity' => 200,
            'newQuantity' => 80,
            'reason' => 'manual_adjustment',
        ]);

        $anomalyTrace = $this->agent->evaluate($canonicalEvent);

        $this->assertNotNull($anomalyTrace, 'A 60% change should trigger detection.');
        $this->assertSame(TraceSeverity::High, $anomalyTrace->severity());
    }

    public function test_detects_increase_anomaly(): void
    {
        $canonicalEvent = $this->makeEvent('inventory.stock.adjusted', [
            'productId' => 'prod_003',
            'locationId' => 'loc_003',
            'previousQuantity' => 50,
            'newQuantity' => 100,
            'reason' => 'stock_correction',
        ]);

        $anomalyTrace = $this->agent->evaluate($canonicalEvent);

        $this->assertNotNull($anomalyTrace, 'A 100% increase should trigger detection.');
        $this->assertSame(TraceSeverity::High, $anomalyTrace->severity());
        $this->assertStringContainsString('increased', $anomalyTrace->detection());
    }

    public function test_ignores_zero_previous_quantity(): void
    {
        $canonicalEvent = $this->makeEvent('inventory.stock.adjusted', [
            'productId' => 'prod_001',
            'locationId' => 'loc_001',
            'previousQuantity' => 0,
            'newQuantity' => 50,
            'reason' => 'initial_stock',
        ]);

        $anomalyTrace = $this->agent->evaluate($canonicalEvent);

        $this->assertNull($anomalyTrace, 'Division by zero case — must return null.');
    }

    public function test_ignores_missing_payload_fields(): void
    {
        $canonicalEvent = $this->makeEvent('inventory.stock.adjusted', [
            'productId' => 'prod_001',
            'locationId' => 'loc_001',
        ]);

        $anomalyTrace = $this->agent->evaluate($canonicalEvent);

        $this->assertNull($anomalyTrace, 'Missing previousQuantity/newQuantity should be handled gracefully.');
    }

    public function test_deterministic_output(): void
    {
        $canonicalEvent = $this->makeEvent('inventory.stock.adjusted', [
            'productId' => 'prod_001',
            'locationId' => 'loc_001',
            'previousQuantity' => 100,
            'newQuantity' => 60,
            'reason' => 'manual_adjustment',
        ]);

        $firstAnomalyTrace = $this->agent->evaluate($canonicalEvent);
        $secondAnomalyTrace = $this->agent->evaluate($canonicalEvent);

        $this->assertNotNull($firstAnomalyTrace);
        $this->assertNotNull($secondAnomalyTrace);

        $this->assertSame($firstAnomalyTrace->detection(), $secondAnomalyTrace->detection());
        $this->assertSame($firstAnomalyTrace->reasoning(), $secondAnomalyTrace->reasoning());
        $this->assertSame($firstAnomalyTrace->suggestion(), $secondAnomalyTrace->suggestion());
        $this->assertSame($firstAnomalyTrace->severity(), $secondAnomalyTrace->severity());
        $this->assertSame($firstAnomalyTrace->traceType(), $secondAnomalyTrace->traceType());
    }

    private function makeEvent(string $eventType, array $eventPayload): CanonicalEvent
    {
        return new CanonicalEvent(
            eventId: 'evt_001',
            eventType: $eventType,
            eventVersion: 1,
            occurredAt: '2026-04-01T12:00:00+00:00',
            actorId: 'user_001',
            correlationId: 'corr_001',
            causationId: 'evt_001',
            payload: $eventPayload,
        );
    }
}
