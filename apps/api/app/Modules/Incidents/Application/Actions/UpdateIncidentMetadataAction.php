<?php

declare(strict_types=1);

namespace App\Modules\Incidents\Application\Actions;

use App\Modules\Audit\Application\Services\AuditLogger;
use App\Modules\Incidents\Application\DTOs\UpdateIncidentMetadataDTO;
use App\Modules\Incidents\Domain\Entities\InventoryIncident;
use App\Modules\Incidents\Domain\Repositories\IncidentRepository;
use App\Modules\Incidents\Infrastructure\Persistence\Eloquent\InventoryIncidentModel;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class UpdateIncidentMetadataAction
{
    public function __construct(
        private readonly IncidentRepository $incidentRepository,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function execute(UpdateIncidentMetadataDTO $data): InventoryIncident
    {
        $incident = $this->incidentRepository->findById($data->incidentId);
        if (!$incident) {
            throw new InvalidArgumentException("Incident {$data->incidentId} not found.");
        }

        $changeset = [];

        if ($data->notes !== null) {
            $changeset['notes'] = $data->notes;
        }
        if ($data->assignedTo !== null) {
            $changeset['assignedTo'] = $data->assignedTo;
        }

        if (empty($changeset)) {
            return $incident;
        }

        InventoryIncidentModel::where('id', $data->incidentId)->update(
            array_merge(
                isset($changeset['notes']) ? ['notes' => $changeset['notes']] : [],
                isset($changeset['assignedTo']) ? ['assigned_to' => $changeset['assignedTo']] : [],
                ['updated_at' => now()],
            )
        );

        $correlationId = request()->header('X-Correlation-ID', Str::uuid()->toString());

        $this->auditLogger->log(
            action: 'incident.metadata_updated',
            entityType: 'InventoryIncident',
            entityId: $data->incidentId,
            changeset: $changeset,
            actorId: $data->performedBy,
            correlationId: $correlationId
        );

        return $this->incidentRepository->findById($data->incidentId);
    }
}
