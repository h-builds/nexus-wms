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
use App\Modules\Locations\Domain\Exceptions\LocationNotFound;
use App\Modules\Product\Domain\Exceptions\ProductNotFound;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;

final class ReportIncidentAction
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly LocationRepository $locationRepository,
        private readonly IncidentRepository $incidentRepository,
        private readonly EventPublisher $eventPublisher,
        private readonly AuditLogger $auditLogger,
        private readonly ConnectionInterface $db,
    ) {}

    public function execute(ReportIncidentDTO $incidentDetails): InventoryIncident
    {
        if ($incidentDetails->idempotencyKey !== null) {
            $existingIncident = $this->incidentRepository->findByIdempotencyKey($incidentDetails->idempotencyKey);
            if ($existingIncident) {
                return $existingIncident;
            }
        }

        $this->ensureProductExists($incidentDetails->productId);
        $this->ensureLocationExists($incidentDetails->locationId);

        $newIncident = $this->createIncidentFrom($incidentDetails);

        return $this->persistIncidentAndDispatchEvents($newIncident, $incidentDetails->correlationId);
    }

    private function createIncidentFrom(ReportIncidentDTO $incidentDetails): InventoryIncident
    {
        $reportedAt = now()->toIso8601String();

        return new InventoryIncident(
            id: Str::uuid()->toString(),
            productId: $incidentDetails->productId,
            locationId: $incidentDetails->locationId,
            type: $incidentDetails->type,
            severity: $incidentDetails->severity,
            status: IncidentStatus::OPEN,
            description: $incidentDetails->description,
            quantityAffected: $incidentDetails->quantityAffected,
            reportedBy: $incidentDetails->reportedBy,
            createdAt: $reportedAt,
            updatedAt: $reportedAt,
            idempotencyKey: $incidentDetails->idempotencyKey
        );
    }

    private function persistIncidentAndDispatchEvents(InventoryIncident $incident, string $correlationId): InventoryIncident
    {
        try {
            $this->db->transaction(function () use ($incident, $correlationId) {
                $this->incidentRepository->save($incident);
                $this->logAuditTrail($incident, $correlationId);
                $this->dispatchReportedEvent($incident, $correlationId);
            });
        } catch (\App\Modules\Incidents\Application\Exceptions\IdempotencyConflictException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new \App\Modules\Incidents\Application\Exceptions\IncidentReportingFailedException(
                'Failed to persist incident and dispatch events.',
                0,
                $e
            );
        }

        return $incident;
    }

    private function ensureProductExists(string $productId): void
    {
        $product = $this->productRepository->findById($productId);
        if (!$product) {
            throw ProductNotFound::withId($productId);
        }
    }

    private function ensureLocationExists(?string $locationId): void
    {
        if ($locationId === null) {
            return;
        }

        $location = $this->locationRepository->findById($locationId);
        if (!$location) {
            throw LocationNotFound::withId($locationId);
        }
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
        $eventPayload = new \App\Modules\Incidents\Application\DTOs\IncidentReportedEventPayload(
            incidentId: $incident->id(),
            productId: $incident->productId(),
            locationId: $incident->locationId(),
            incidentType: $incident->type()->value,
            description: $incident->description(),
        );

        $this->eventPublisher->publish(
            eventType: 'incident.reported',
            payload: $eventPayload,
            actorId: $incident->reportedBy(),
            correlationId: $correlationId
        );
    }
}
