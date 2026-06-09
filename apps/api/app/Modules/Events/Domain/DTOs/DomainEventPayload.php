<?php

declare(strict_types=1);

namespace App\Modules\Events\Domain\DTOs;

interface DomainEventPayload
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
