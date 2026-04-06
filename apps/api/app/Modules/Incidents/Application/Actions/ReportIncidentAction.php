<?php

declare(strict_types=1);

namespace App\Modules\Incidents\Application\Actions;

use App\Modules\Events\Application\Services\EventPublisher;
use App\Modules\Incidents\Application\DTOs\ReportIncidentDTO;
use App\Modules\Incidents\Domain\Entities\InventoryIncident;
use App\Modules\Incidents\Domain\Enums\IncidentSeverity;
use App\Modules\Incidents\Domain\Enums\IncidentStatus;
use App\Modules\Incidents\Domain\Enums\IncidentType;
use App\Modules\Incidents\Domain\Repositories\IncidentRepository;
use App\Modules\Locations\Domain\Repositories\LocationRepository;
use App\Modules\Product\Domain\Repositories\ProductRepository;
use App\Modules\Audit\Application\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class ReportIncidentAction
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly LocationRepository $locationRepository,
        private readonly IncidentRepository $incidentRepository,
        private readonly EventPublisher $eventPublisher,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function execute(ReportIncidentDTO $incidentDetails): InventoryIncident
    {
        if ($incidentDetails->idempotencyKey !== null) {
            $existing = $this->incidentRepository->findByIdempotencyKey($incidentDetails->idempotencyKey);
            if ($existing) {
                return $existing;
            }
        }

        $this->ensureProductExists($incidentDetails->productId);
        $this->ensureLocationExists($incidentDetails->locationId);

        $type = $this->parseIncidentType($incidentDetails->type);
        $severity = $this->parseIncidentSeverity($incidentDetails->severity);

        $incidentId = Str::uuid()->toString();
        $now = now()->toIso8601String();
        $correlationId = $incidentDetails->correlationId;

        $incident = new InventoryIncident(
            id: $incidentId,
            productId: $incidentDetails->productId,
            locationId: $incidentDetails->locationId,
            type: $type,
            severity: $severity,
            status: IncidentStatus::OPEN,
            description: $incidentDetails->description,
            quantityAffected: $incidentDetails->quantityAffected,
            reportedBy: $incidentDetails->reportedBy,
            createdAt: $now,
            updatedAt: $now,
            idempotencyKey: $incidentDetails->idempotencyKey
        );

        DB::transaction(function () use ($incident, $correlationId) {
            $this->incidentRepository->save($incident);
            $this->logAuditTrail($incident, $correlationId);
            $this->dispatchReportedEvent($incident, $correlationId);
        });

        return $incident;
    }

    private function ensureProductExists(string $productId): void
    {
        $product = $this->productRepository->findById($productId);
        if (!$product) {
            throw new InvalidArgumentException("Product {$productId} not found.");
        }
    }

    private function ensureLocationExists(?string $locationId): void
    {
        if ($locationId === null) {
            return;
        }

        $location = $this->locationRepository->findById($locationId);
        if (!$location) {
            throw new InvalidArgumentException("Location {$locationId} not found.");
        }
    }

    private function parseIncidentType(string $typeValue): IncidentType
    {
        $type = IncidentType::tryFrom($typeValue);
        if (!$type) {
            throw new InvalidArgumentException("Invalid incident type: {$typeValue}.");
        }
        return $type;
    }

    private function parseIncidentSeverity(string $severityValue): IncidentSeverity
    {
        $severity = IncidentSeverity::tryFrom($severityValue);
        if (!$severity) {
            throw new InvalidArgumentException("Invalid incident severity: {$severityValue}.");
        }
        return $severity;
    }

    private function logAuditTrail(InventoryIncident $incident, string $correlationId): void
    {
        $this->auditLogger->log(
            action: 'incident.reported',
            entityType: 'InventoryIncident',
            entityId: $incident->id(),
            changeset: [
                'status' => $incident->status()->value,
                'type' => $incident->type()->value,
                'severity' => $incident->severity()->value,
                'quantityAffected' => $incident->quantityAffected(),
                'locationId' => $incident->locationId(),
            ],
            actorId: $incident->reportedBy(),
            correlationId: $correlationId
        );
    }

    private function dispatchReportedEvent(InventoryIncident $incident, string $correlationId): void
    {
        $eventPayload = [
            'incidentId' => $incident->id(),
            'productId' => $incident->productId(),
            'locationId' => $incident->locationId(),
            'type' => $incident->type()->value,
            'description' => $incident->description(),
        ];

        $this->eventPublisher->publish(
            eventType: 'incident.reported',
            payload: $eventPayload,
            actorId: $incident->reportedBy(),
            correlationId: $correlationId
        );
    }
}
