<?php

declare(strict_types=1);

namespace App\Modules\Incidents\Domain\Entities;

use App\Modules\Incidents\Domain\Enums\IncidentStatus;
use App\Modules\Incidents\Domain\Enums\IncidentType;
use App\Modules\Incidents\Domain\Enums\IncidentSeverity;
use InvalidArgumentException;

final class InventoryIncident
{
    public function __construct(
        private readonly string $id,
        private readonly string $productId,
        private readonly ?string $locationId,
        private readonly IncidentType $type,
        private readonly IncidentSeverity $severity,
        private IncidentStatus $status,
        private readonly string $description,
        private readonly ?int $quantityAffected,
        private readonly string $reportedBy,
        private readonly string $createdAt,
        private string $updatedAt,
        private readonly ?string $idempotencyKey = null,
        private ?string $assignedTo = null,
        private ?string $notes = null,
    ) {
        $this->enforceInvariants();
    }

    private function enforceInvariants(): void
    {
        if (trim($this->description) === '') {
            throw new InvalidArgumentException("Incident description cannot be empty.");
        }

        if ($this->quantityAffected !== null && $this->quantityAffected < 0) {
            throw new InvalidArgumentException("quantityAffected must be a positive integer or null.");
        }
    }

    public function transitionTo(IncidentStatus $newStatus, string $timestamp): void
    {
        if (!$this->status->canTransitionTo($newStatus)) {
            throw new InvalidArgumentException("Cannot transition incident status from {$this->status->value} to {$newStatus->value}.");
        }

        $this->status = $newStatus;
        $this->updatedAt = $timestamp;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function productId(): string
    {
        return $this->productId;
    }

    public function locationId(): ?string
    {
        return $this->locationId;
    }

    public function type(): IncidentType
    {
        return $this->type;
    }

    public function severity(): IncidentSeverity
    {
        return $this->severity;
    }

    public function status(): IncidentStatus
    {
        return $this->status;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function quantityAffected(): ?int
    {
        return $this->quantityAffected;
    }

    public function reportedBy(): string
    {
        return $this->reportedBy;
    }

    public function idempotencyKey(): ?string
    {
        return $this->idempotencyKey;
    }

    public function createdAt(): string
    {
        return $this->createdAt;
    }

    public function updatedAt(): string
    {
        return $this->updatedAt;
    }

    public function assignedTo(): ?string
    {
        return $this->assignedTo;
    }

    public function notes(): ?string
    {
        return $this->notes;
    }
}
