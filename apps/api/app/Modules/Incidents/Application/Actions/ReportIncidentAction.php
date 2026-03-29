<?php

declare(strict_types=1);

namespace App\Modules\Incidents\Application\Actions;

use App\Modules\Events\Application\Services\OutboxDispatcher;
use App\Modules\Events\Infrastructure\Persistence\Eloquent\EventOutboxModel;
use App\Modules\Incidents\Application\DTOs\ReportIncidentDTO;
use App\Modules\Incidents\Domain\Entities\InventoryIncident;
use App\Modules\Incidents\Domain\Enums\IncidentSeverity;
use App\Modules\Incidents\Domain\Enums\IncidentStatus;
use App\Modules\Incidents\Domain\Enums\IncidentType;
use App\Modules\Incidents\Domain\Repositories\IncidentRepository;
use App\Modules\Incidents\Infrastructure\Persistence\Eloquent\InventoryIncidentModel;
use App\Modules\Locations\Domain\Repositories\LocationRepository;
use App\Modules\Product\Domain\Repositories\ProductRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class ReportIncidentAction
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly LocationRepository $locationRepository,
        private readonly IncidentRepository $incidentRepository,
        private readonly OutboxDispatcher $outboxDispatcher,
        private readonly \App\Modules\Audit\Application\Services\AuditLogger $auditLogger,
    ) {}

    public function execute(ReportIncidentDTO $incidentData): InventoryIncident
    {
        if ($incidentData->idempotencyKey !== null) {
            $existing = $this->incidentRepository->findByIdempotencyKey($incidentData->idempotencyKey);
            if ($existing) {
                return $existing;
            }
        }

        $product = $this->productRepository->findById($incidentData->productId);
        if (!$product) {
            throw new InvalidArgumentException("Product {$incidentData->productId} not found.");
        }

        if ($incidentData->locationId !== null) {
            $location = $this->locationRepository->findById($incidentData->locationId);
            if (!$location) {
                throw new InvalidArgumentException("Location {$incidentData->locationId} not found.");
            }
        }

        $type = IncidentType::tryFrom($incidentData->type);
        if (!$type) {
            throw new InvalidArgumentException("Invalid incident type: {$incidentData->type}.");
        }

        $severity = IncidentSeverity::tryFrom($incidentData->severity);
        if (!$severity) {
            throw new InvalidArgumentException("Invalid incident severity: {$incidentData->severity}.");
        }

        $incidentId = Str::uuid()->toString();
        $now = now()->toIso8601String();

        $incident = new InventoryIncident(
            id: $incidentId,
            productId: $incidentData->productId,
            locationId: $incidentData->locationId,
            type: $type,
            severity: $severity,
            status: IncidentStatus::OPEN,
            description: $incidentData->description,
            quantityAffected: $incidentData->quantityAffected,
            reportedBy: $incidentData->reportedBy,
            createdAt: $now,
            updatedAt: $now,
            idempotencyKey: $incidentData->idempotencyKey
        );

        $outboxEventId = Str::uuid()->toString();
        $correlationId = request()->header('X-Correlation-ID', Str::uuid()->toString());

        DB::transaction(function () use ($incident, $outboxEventId, $correlationId) {
            $this->incidentRepository->save($incident);

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

            $eventPayload = [
                'incidentId' => $incident->id(),
                'productId' => $incident->productId(),
                'locationId' => $incident->locationId(),
                'type' => $incident->type()->value,
                'description' => $incident->description(),
            ];

            EventOutboxModel::create([
                'event_id' => $outboxEventId,
                'event_type' => 'incident.reported',
                'event_version' => 1,
                'occurred_at' => now(),
                'actor_id' => $incident->reportedBy(),
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
