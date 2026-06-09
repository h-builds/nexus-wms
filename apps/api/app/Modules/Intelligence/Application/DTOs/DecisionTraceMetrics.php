<?php

declare(strict_types=1);

namespace App\Modules\Intelligence\Application\DTOs;

final readonly class DecisionTraceMetrics
{
    public function __construct(
        public int $totalTraces,
        public int $advisoryCount,
        public int $acknowledgedCount,
        public int $actedUponCount,
        public int $dismissedCount,
        public int $criticalCount,
        public int $highCount,
        public int $mediumCount,
        public int $lowCount,
        public int $inventoryDomainCount,
        public int $incidentsDomainCount,
        public int $movementsDomainCount,
        public int $monitoringDomainCount,
    ) {}

    /**
     * @return array<string, int>
     */
    public function toArray(): array
    {
        return [
            'totalTraces' => $this->totalTraces,
            'advisoryCount' => $this->advisoryCount,
            'acknowledgedCount' => $this->acknowledgedCount,
            'actedUponCount' => $this->actedUponCount,
            'dismissedCount' => $this->dismissedCount,
            'criticalCount' => $this->criticalCount,
            'highCount' => $this->highCount,
            'mediumCount' => $this->mediumCount,
            'lowCount' => $this->lowCount,
            'inventoryDomainCount' => $this->inventoryDomainCount,
            'incidentsDomainCount' => $this->incidentsDomainCount,
            'movementsDomainCount' => $this->movementsDomainCount,
            'monitoringDomainCount' => $this->monitoringDomainCount,
        ];
    }
}
