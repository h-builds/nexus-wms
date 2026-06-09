<?php

declare(strict_types=1);

namespace App\Modules\Intelligence\Application\Agents;

use App\Modules\Intelligence\Domain\Entities\DecisionTrace;
use App\Modules\Intelligence\Domain\Enums\AgentDomain;
use App\Modules\Intelligence\Domain\Enums\TraceSeverity;
use App\Modules\Intelligence\Domain\Enums\TraceStatus;
use App\Modules\Intelligence\Domain\Enums\TraceType;
use Illuminate\Support\Str;

final class InventoryAnomalyAgent implements DecisionAgent
{
    private const string AGENT_ID = 'inventory-anomaly-agent';
    private const string TARGET_EVENT_TYPE = 'inventory.stock.adjusted';
    private const float THRESHOLD_PERCENTAGE = 0.30;

    public function evaluate(CanonicalEvent $event): ?DecisionTrace
    {
        if (!$this->isTargetEvent($event)) {
            return null;
        }

        $quantities = $this->extractQuantities($event->payload);
        if ($quantities === null || $quantities['previous'] === 0) {
            return null;
        }

        $changePercentage = $this->calculateChangePercentage($quantities['previous'], $quantities['new']);
        if ($changePercentage < self::THRESHOLD_PERCENTAGE) {
            return null;
        }

        return $this->createAnomalyTrace($event, $quantities['previous'], $quantities['new'], $changePercentage);
    }

    private function isTargetEvent(CanonicalEvent $event): bool
    {
        return $event->eventType === self::TARGET_EVENT_TYPE && is_array($event->payload);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{previous: int, new: int}|null
     */
    private function extractQuantities(array $payload): ?array
    {
        $previousQuantity = $payload['previousQuantity'] ?? null;
        $newQuantity = $payload['newQuantity'] ?? null;

        if ($previousQuantity === null || $newQuantity === null) {
            return null;
        }

        if (!is_numeric($previousQuantity) || !is_numeric($newQuantity)) {
            return null;
        }

        return [
            'previous' => (int) $previousQuantity,
            'new' => (int) $newQuantity,
        ];
    }

    private function calculateChangePercentage(int $previousQuantity, int $newQuantity): float
    {
        $absoluteChange = abs($newQuantity - $previousQuantity);
        return $absoluteChange / abs($previousQuantity);
    }

    private function createAnomalyTrace(
        CanonicalEvent $event,
        int $previousQuantity,
        int $newQuantity,
        float $changePercentage
    ): DecisionTrace {
        $severity = $changePercentage >= 0.50 ? TraceSeverity::High : TraceSeverity::Medium;

        /** @var array<string, mixed> $payload */
        $payload = $event->payload;
        $productId = is_string($payload['productId'] ?? null) ? $payload['productId'] : 'unknown';
        $locationId = is_string($payload['locationId'] ?? null) ? $payload['locationId'] : 'unknown';
        
        $direction = $newQuantity < $previousQuantity ? 'decreased' : 'increased';
        $percentDisplay = round($changePercentage * 100, 1);
        $thresholdDisplay = self::THRESHOLD_PERCENTAGE * 100;

        return new DecisionTrace(
            id: Str::uuid()->toString(),
            traceType: TraceType::AnomalyDetection,
            agentId: self::AGENT_ID,
            agentDomain: AgentDomain::Inventory,
            detection: "Stock for product {$productId} at location {$locationId} {$direction} by {$percentDisplay}% (from {$previousQuantity} to {$newQuantity}).",
            reasoning: "The adjustment magnitude ({$percentDisplay}%) exceeds the {$thresholdDisplay}% anomaly threshold. This deviation from normal stock levels may indicate a counting error, theft, or unrecorded movement.",
            suggestion: "Review recent movements and adjustments for product {$productId} at location {$locationId}. Verify physical stock count against system records.",
            severity: $severity,
            causationId: $event->eventId,
            correlationId: $event->correlationId,
            triggerEventIds: [$event->eventId],
            status: TraceStatus::Advisory,
            createdAt: now()->toIso8601String(),
        );
    }
}
