<?php

declare(strict_types=1);

namespace App\Modules\Audit\Domain\Entities;

use App\Modules\Audit\Domain\Enums\AuditActorType;

final class AuditLog
{
    public function __construct(
        private readonly string $id,
        private readonly ?string $actorId,
        private readonly AuditActorType $actorType,
        private readonly string $action,
        private readonly string $entityType,
        private readonly string $entityId,
        private readonly array $changeset,
        private readonly string $correlationId,
        private readonly string $timestamp,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function actorId(): ?string
    {
        return $this->actorId;
    }

    public function actorType(): AuditActorType
    {
        return $this->actorType;
    }

    public function action(): string
    {
        return $this->action;
    }

    public function entityType(): string
    {
        return $this->entityType;
    }

    public function entityId(): string
    {
        return $this->entityId;
    }

    public function changeset(): array
    {
        return $this->changeset;
    }

    public function correlationId(): string
    {
        return $this->correlationId;
    }

    public function timestamp(): string
    {
        return $this->timestamp;
    }
}
