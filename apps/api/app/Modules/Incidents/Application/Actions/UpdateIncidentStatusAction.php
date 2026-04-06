<?php

declare(strict_types=1);

namespace App\Modules\Incidents\Application\Actions;

use App\Modules\Events\Application\Services\EventPublisher;
use App\Modules\Incidents\Application\DTOs\UpdateIncidentStatusDTO;
use App\Modules\Incidents\Domain\Entities\InventoryIncident;
use App\Modules\Incidents\Domain\Enums\IncidentStatus;
use App\Modules\Incidents\Domain\Repositories\IncidentRepository;
use App\Modules\Audit\Application\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class UpdateIncidentStatusAction
{
    public function __construct(
        private readonly IncidentRepository $incidentRepository,
        private readonly EventPublisher $eventPublisher,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function execute(UpdateIncidentStatusDTO $statusTransition): InventoryIncident
    {
        $newStatus = $this->parseIncidentStatus($statusTransition->status);
        $incident = $this->ensureIncidentExists($statusTransition->incidentId);

        $previousStatus = $incident->status();
        $correlationId = $statusTransition->correlationId;

        DB::transaction(function () use ($incident, $newStatus, $statusTransition, $previousStatus, $correlationId) {
            $incident->transitionTo($newStatus, now()->toIso8601String());
            $this->incidentRepository->save($incident);

            $this->logAuditTrail($incident, $previousStatus, $newStatus, $statusTransition->performedBy, $correlationId);
            $this->dispatchStatusUpdatedEvent($incident, $previousStatus, $newStatus, $statusTransition->performedBy, $correlationId);
        });

        return $incident;
    }

    private function parseIncidentStatus(string $statusValue): IncidentStatus
    {
        $newStatus = IncidentStatus::tryFrom($statusValue);
        if (!$newStatus) {
            throw new InvalidArgumentException("Invalid incident status: {$statusValue}.");
        }
        return $newStatus;
    }

    private function ensureIncidentExists(string $incidentId): InventoryIncident
    {
        $incident = $this->incidentRepository->findById($incidentId);
        if (!$incident) {
            throw new InvalidArgumentException("Incident {$incidentId} not found.");
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
        $eventPayload = [
            'incidentId' => $incident->id(),
            'previousStatus' => $previousStatus->value,
            'newStatus' => $newStatus->value,
        ];

        $this->eventPublisher->publish(
            eventType: 'incident.status.updated',
            payload: $eventPayload,
            actorId: $performedBy,
            correlationId: $correlationId
        );
    }
}
