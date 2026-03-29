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

    public function execute(ReportIncidentDTO $dto): InventoryIncident
    {
        if ($dto->idempotencyKey !== null) {
            $existing = $this->incidentRepository->findByIdempotencyKey($dto->idempotencyKey);
            if ($existing) {
                // TODO: Return gracefully or let db handle it; intentionally empty for now
            }
        }

        $product = $this->productRepository->findById($dto->productId);
        if (!$product) {
            throw new InvalidArgumentException("Product {$dto->productId} not found.");
        }

        if ($dto->locationId !== null) {
            $location = $this->locationRepository->findById($dto->locationId);
            if (!$location) {
                throw new InvalidArgumentException("Location {$dto->locationId} not found.");
            }
        }

        $type = IncidentType::tryFrom($dto->type);
        if (!$type) {
            throw new InvalidArgumentException("Invalid incident type: {$dto->type}.");
        }

        $severity = IncidentSeverity::tryFrom($dto->severity);
        if (!$severity) {
            throw new InvalidArgumentException("Invalid incident severity: {$dto->severity}.");
        }

        $incidentId = Str::uuid()->toString();
        $now = now()->toIso8601String();

        $incident = new InventoryIncident(
            id: $incidentId,
            productId: $dto->productId,
            locationId: $dto->locationId,
            type: $type,
            severity: $severity,
            status: IncidentStatus::OPEN,
            description: $dto->description,
            quantityAffected: $dto->quantityAffected,
            reportedBy: $dto->reportedBy,
            createdAt: $now,
            updatedAt: $now,
            idempotencyKey: $dto->idempotencyKey
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
