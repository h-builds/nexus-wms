<?php

declare(strict_types=1);

namespace App\Modules\Intelligence\Domain\Entities;

use App\Modules\Intelligence\Domain\Enums\AgentDomain;
use App\Modules\Intelligence\Domain\Enums\TraceSeverity;
use App\Modules\Intelligence\Domain\Enums\TraceStatus;
use App\Modules\Intelligence\Domain\Enums\TraceType;
use InvalidArgumentException;

/**
 * Append-only advisory record — no deletes permitted; traces are permanent accountability records.
 */
final class DecisionTrace
{
    /**
     * @param list<string> $triggerEventIds
     */
    public function __construct(
        private readonly string $id,
        private readonly TraceType $traceType,
        private readonly string $agentId,
        private readonly AgentDomain $agentDomain,
        private readonly string $detection,
        private readonly string $reasoning,
        private readonly string $suggestion,
        private readonly TraceSeverity $severity,
        private readonly string $causationId,
        private readonly string $correlationId,
        private readonly array $triggerEventIds,
        private TraceStatus $status,
        private readonly string $createdAt,
        private ?string $updatedAt = null,
        private ?string $actedUponAt = null,
        private ?string $actedUponBy = null,
    ) {
        $this->enforceInvariants();
    }

    private function enforceInvariants(): void
    {
        if (trim($this->causationId) === '') {
            throw new InvalidArgumentException('DecisionTrace requires a causationId.');
        }

        if (trim($this->correlationId) === '') {
            throw new InvalidArgumentException('DecisionTrace requires a correlationId.');
        }

        if (trim($this->detection) === '') {
            throw new InvalidArgumentException('DecisionTrace detection cannot be empty.');
        }

        if (trim($this->reasoning) === '') {
            throw new InvalidArgumentException('DecisionTrace reasoning cannot be empty.');
        }

        if (trim($this->suggestion) === '') {
            throw new InvalidArgumentException('DecisionTrace suggestion cannot be empty.');
        }

        foreach ($this->triggerEventIds as $index => $eventId) {
            if (!is_string($eventId) || trim($eventId) === '') {
                throw new InvalidArgumentException(
                    sprintf('DecisionTrace triggerEventIds[%d] must be a non-empty string.', $index)
                );
            }
        }
    }

    public function acknowledge(string $timestamp): void
    {
        $this->transitionStatus(TraceStatus::Acknowledged, actorId: null, timestamp: $timestamp);
    }

    public function actUpon(string $actorId, string $timestamp): void
    {
        if (trim($actorId) === '') {
            throw new InvalidArgumentException('actorId is required to act upon a DecisionTrace.');
        }

        $this->transitionStatus(TraceStatus::ActedUpon, actorId: $actorId, timestamp: $timestamp);
        $this->actedUponAt = $timestamp;
        $this->actedUponBy = $actorId;
    }

    public function dismiss(string $actorId, string $timestamp): void
    {
        if (trim($actorId) === '') {
            throw new InvalidArgumentException('actorId is required to dismiss a DecisionTrace.');
        }

        $this->transitionStatus(TraceStatus::Dismissed, actorId: $actorId, timestamp: $timestamp);
        $this->actedUponAt = $timestamp;
        $this->actedUponBy = $actorId;
    }

    private function transitionStatus(TraceStatus $target, ?string $actorId, string $timestamp): void
    {
        if (!$this->status->canTransitionTo($target)) {
            throw new InvalidArgumentException(
                "Cannot transition DecisionTrace status from {$this->status->value} to {$target->value}."
            );
        }

        $this->status = $target;
        $this->updatedAt = $timestamp;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function traceType(): TraceType
    {
        return $this->traceType;
    }

    public function agentId(): string
    {
        return $this->agentId;
    }

    public function agentDomain(): AgentDomain
    {
        return $this->agentDomain;
    }

    public function detection(): string
    {
        return $this->detection;
    }

    public function reasoning(): string
    {
        return $this->reasoning;
    }

    public function suggestion(): string
    {
        return $this->suggestion;
    }

    public function severity(): TraceSeverity
    {
        return $this->severity;
    }

    public function causationId(): string
    {
        return $this->causationId;
    }

    public function correlationId(): string
    {
        return $this->correlationId;
    }

    /**
     * @return list<string>
     */
    public function triggerEventIds(): array
    {
        return $this->triggerEventIds;
    }

    public function status(): TraceStatus
    {
        return $this->status;
    }

    public function actedUponAt(): ?string
    {
        return $this->actedUponAt;
    }

    public function actedUponBy(): ?string
    {
        return $this->actedUponBy;
    }

    public function createdAt(): string
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?string
    {
        return $this->updatedAt;
    }
}
