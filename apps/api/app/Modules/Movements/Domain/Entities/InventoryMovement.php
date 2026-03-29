<?php

declare(strict_types=1);

namespace App\Modules\Movements\Domain\Entities;

use App\Modules\Movements\Domain\Enums\MovementType;
use App\Modules\Movements\Domain\Enums\AdjustmentReason;
use InvalidArgumentException;

final class InventoryMovement
{
    public function __construct(
        private readonly string $id,
        private readonly string $productId,
        private readonly ?string $fromLocationId,
        private readonly ?string $toLocationId,
        private readonly MovementType $type,
        private readonly int $quantity,
        private readonly ?string $reference,
        private readonly ?string $lotNumber,
        private readonly ?string $reason,
        private readonly string $performedBy,
        private readonly string $performedAt,
        private readonly ?string $idempotencyKey = null,
        private readonly ?string $createdAt = null,
        private readonly ?string $updatedAt = null,
    ) {
        $this->enforceInvariants();
    }

    private function enforceInvariants(): void
    {
        if ($this->quantity <= 0) {
            throw new InvalidArgumentException("Quantity must be greater than zero.");
        }

        if ($this->type->requiresFromLocation() && $this->fromLocationId === null) {
            throw new InvalidArgumentException("Movement type {$this->type->value} requires fromLocationId.");
        }

        if ($this->type->requiresToLocation() && $this->toLocationId === null) {
            throw new InvalidArgumentException("Movement type {$this->type->value} requires toLocationId.");
        }

        if ($this->type->forbidsSameLocations() && $this->fromLocationId !== null && $this->fromLocationId === $this->toLocationId) {
            throw new InvalidArgumentException("fromLocationId and toLocationId cannot be the same for {$this->type->value}.");
        }

        if ($this->type === MovementType::ADJUSTMENT) {
            if ($this->reason === null) {
                throw new InvalidArgumentException("Adjustment movement requires a reason.");
            }
            if (AdjustmentReason::tryFrom($this->reason) === null) {
                throw new InvalidArgumentException("Invalid adjustment reason: {$this->reason}.");
            }
        }
    }

    public function id(): string
    {
        return $this->id;
    }

    public function productId(): string
    {
        return $this->productId;
    }

    public function fromLocationId(): ?string
    {
        return $this->fromLocationId;
    }

    public function toLocationId(): ?string
    {
        return $this->toLocationId;
    }

    public function type(): MovementType
    {
        return $this->type;
    }

    public function quantity(): int
    {
        return $this->quantity;
    }

    public function reference(): ?string
    {
        return $this->reference;
    }

    public function lotNumber(): ?string
    {
        return $this->lotNumber;
    }

    public function reason(): ?string
    {
        return $this->reason;
    }

    public function performedBy(): string
    {
        return $this->performedBy;
    }

    public function performedAt(): string
    {
        return $this->performedAt;
    }

    public function idempotencyKey(): ?string
    {
        return $this->idempotencyKey;
    }

    public function createdAt(): ?string
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?string
    {
        return $this->updatedAt;
    }
}
