<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Domain\Entities;

use App\Modules\Inventory\Domain\Enums\InventoryStatus;
use InvalidArgumentException;

final class StockItem
{
    public function __construct(
        private readonly string $id,
        private readonly string $productId,
        private readonly string $locationId,
        private readonly int $quantityOnHand,
        private readonly int $quantityAvailable,
        private readonly int $quantityBlocked,
        private readonly ?string $lotNumber,
        private readonly ?string $serialNumber,
        private readonly ?string $receivedAt,
        private readonly ?string $expiresAt,
        private readonly InventoryStatus $status,
        private readonly int $version = 1,
        private readonly ?string $updatedAt = null,
    ) {
        $this->enforceInvariants();
    }



    public function id(): string
    {
        return $this->id;
    }

    public function productId(): string
    {
        return $this->productId;
    }

    public function locationId(): string
    {
        return $this->locationId;
    }

    public function quantityOnHand(): int
    {
        return $this->quantityOnHand;
    }

    public function quantityAvailable(): int
    {
        return $this->quantityAvailable;
    }

    public function quantityBlocked(): int
    {
        return $this->quantityBlocked;
    }

    public function lotNumber(): ?string
    {
        return $this->lotNumber;
    }

    public function serialNumber(): ?string
    {
        return $this->serialNumber;
    }

    public function receivedAt(): ?string
    {
        return $this->receivedAt;
    }

    public function expiresAt(): ?string
    {
        return $this->expiresAt;
    }

    public function status(): InventoryStatus
    {
        return $this->status;
    }

    public function version(): int
    {
        return $this->version;
    }

    public function updatedAt(): ?string
    {
        return $this->updatedAt;
    }



    private function enforceInvariants(): void
    {
        if ($this->quantityAvailable < 0) {
            throw new InvalidArgumentException(
                "StockItem [{$this->id}]: quantityAvailable cannot be negative (got {$this->quantityAvailable})."
            );
        }

        if ($this->quantityBlocked < 0) {
            throw new InvalidArgumentException(
                "StockItem [{$this->id}]: quantityBlocked cannot be negative (got {$this->quantityBlocked})."
            );
        }

        $expectedOnHand = $this->quantityAvailable + $this->quantityBlocked;

        if ($this->quantityOnHand !== $expectedOnHand) {
            throw new InvalidArgumentException(
                "StockItem [{$this->id}]: quantityOnHand ({$this->quantityOnHand}) must equal quantityAvailable ({$this->quantityAvailable}) + quantityBlocked ({$this->quantityBlocked}) = {$expectedOnHand}."
            );
        }

        if ($this->version < 1) {
            throw new InvalidArgumentException(
                "StockItem [{$this->id}]: version must be >= 1 (got {$this->version})."
            );
        }
    }
}
