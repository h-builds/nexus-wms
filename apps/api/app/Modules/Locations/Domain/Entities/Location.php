<?php

declare(strict_types=1);

namespace App\Modules\Locations\Domain\Entities;

final class Location
{
    public function __construct(
        private readonly string $id,
        private readonly string $warehouseCode,
        private readonly string $zone,
        private readonly string $aisle,
        private readonly string $rack,
        private readonly string $level,
        private readonly string $bin,
        private readonly string $label,
        private readonly bool $isBlocked = false,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function warehouseCode(): string
    {
        return $this->warehouseCode;
    }

    public function zone(): string
    {
        return $this->zone;
    }

    public function aisle(): string
    {
        return $this->aisle;
    }

    public function rack(): string
    {
        return $this->rack;
    }

    public function level(): string
    {
        return $this->level;
    }

    public function bin(): string
    {
        return $this->bin;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function isBlocked(): bool
    {
        return $this->isBlocked;
    }
}
