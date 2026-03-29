<?php

declare(strict_types=1);

namespace App\Modules\Incidents\Application\Actions;

use App\Modules\Events\Application\Services\OutboxDispatcher;
use App\Modules\Events\Infrastructure\Persistence\Eloquent\EventOutboxModel;
use App\Modules\Incidents\Application\DTOs\UpdateIncidentStatusDTO;
use App\Modules\Incidents\Domain\Entities\InventoryIncident;
use App\Modules\Incidents\Domain\Enums\IncidentStatus;
use App\Modules\Incidents\Domain\Repositories\IncidentRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class UpdateIncidentStatusAction
{
    public function __construct(
        private readonly IncidentRepository $incidentRepository,
        private readonly OutboxDispatcher $outboxDispatcher,
    ) {}

    public function execute(UpdateIncidentStatusDTO $dto): InventoryIncident
    {
        $newStatus = IncidentStatus::tryFrom($dto->status);
        if (!$newStatus) {
            throw new InvalidArgumentException("Invalid incident status: {$dto->status}.");
        }

        $incident = $this->incidentRepository->findById($dto->incidentId);
        if (!$incident) {
            throw new InvalidArgumentException("Incident {$dto->incidentId} not found.");
        }

        $previousStatus = $incident->status();

        DB::transaction(function () use ($incident, $newStatus, $dto, $previousStatus, &$outboxEventId) {
            $incident->transitionTo($newStatus, now()->toIso8601String());

            $this->incidentRepository->save($incident);

            $outboxEventId = Str::uuid()->toString();
            $correlationId = request()->header('X-Correlation-ID', Str::uuid()->toString());

            $eventPayload = [
                'incidentId' => $incident->id(),
                'previousStatus' => $previousStatus->value,
                'newStatus' => $newStatus->value,
            ];

            EventOutboxModel::create([
                'event_id' => $outboxEventId,
                'event_type' => 'incident.status.updated',
                'event_version' => 1,
                'occurred_at' => now(),
                'actor_id' => $dto->performedBy,
                'correlation_id' => $correlationId,
                'causation_id' => $outboxEventId,
                'payload' => $eventPayload,
                'dispatched' => false,
            ]);
        });

        $this->outboxDispatcher->dispatchAndMark($outboxEventId, new \stdClass());

        return $incident;
    }
}
