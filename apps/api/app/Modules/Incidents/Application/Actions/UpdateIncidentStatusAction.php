<?php

declare(strict_types=1);

namespace App\Modules\Incidents\Application\Actions;

use App\Modules\Events\Application\Services\EventPublisher;
use App\Modules\Incidents\Application\DTOs\UpdateIncidentStatusDTO;
use App\Modules\Incidents\Domain\Entities\InventoryIncident;
use App\Modules\Incidents\Domain\Enums\IncidentStatus;
use App\Modules\Incidents\Domain\Repositories\IncidentRepository;
use App\Modules\Audit\Application\Services\AuditLogger;
use App\Modules\Incidents\Domain\Exceptions\IncidentNotFound;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;

final class UpdateIncidentStatusAction
{
    public function __construct(
        private readonly IncidentRepository $incidentRepository,
        private readonly EventPublisher $eventPublisher,
        private readonly AuditLogger $auditLogger,
        private readonly ConnectionInterface $db,
    ) {}

    public function execute(UpdateIncidentStatusDTO $statusTransition): InventoryIncident
    {
        $incident = $this->ensureIncidentExists($statusTransition->incidentId);

        return $this->transitionIncidentStatus($incident, $statusTransition);
    }

    private function transitionIncidentStatus(InventoryIncident $incident, UpdateIncidentStatusDTO $statusTransition): InventoryIncident
    {
        $newStatus = $statusTransition->incidentStatus;
        $previousStatus = $incident->status();
        $correlationId = $statusTransition->correlationId;

        try {
            $this->db->transaction(function () use ($incident, $newStatus, $statusTransition, $previousStatus, $correlationId) {
                $incident->transitionTo($newStatus, now()->toIso8601String());
                $this->incidentRepository->save($incident);

                $this->logAuditTrail($incident, $previousStatus, $newStatus, $statusTransition->performedBy, $correlationId);
                $this->dispatchStatusUpdatedEvent($incident, $previousStatus, $newStatus, $statusTransition->performedBy, $correlationId);
            });
        } catch (\App\Modules\Incidents\Application\Exceptions\IdempotencyConflictException $e) {
            throw $e;
        }

        return $incident;
    }

    private function ensureIncidentExists(string $incidentId): InventoryIncident
    {
        $incident = $this->incidentRepository->findById($incidentId);
        if (!$incident) {
            throw IncidentNotFound::withId($incidentId);
        }
        return $incident;
    }

    private function logAuditTrail(
        InventoryIncident $incident,
        IncidentStatus $previousStatus,
        IncidentStatus $newStatus,
        string $performedBy,
        string $correlationId
    ): void {
        $this->auditLogger->log(
            action: 'incident.status_updated',
            entityType: 'InventoryIncident',
            entityId: $incident->id(),
            changeset: [
                'previous_status' => $previousStatus->value,
                'new_status' => $newStatus->value,
            ],
            actorId: $performedBy,
            correlationId: $correlationId
        );
    }

    private function dispatchStatusUpdatedEvent(
        InventoryIncident $incident,
        IncidentStatus $previousStatus,
        IncidentStatus $newStatus,
        string $performedBy,
        string $correlationId
    ): void {
        $eventPayload = new \App\Modules\Incidents\Application\DTOs\IncidentStatusUpdatedEventPayload(
            incidentId: $incident->id(),
            previousStatus: $previousStatus->value,
            newStatus: $newStatus->value,
        );

        $this->eventPublisher->publish(
            eventType: 'incident.status.updated',
            payload: $eventPayload,
            actorId: $performedBy,
            correlationId: $correlationId
        );
    }
}
