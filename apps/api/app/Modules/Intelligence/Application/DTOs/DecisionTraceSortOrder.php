<?php

declare(strict_types=1);

namespace App\Modules\Intelligence\Application\DTOs;

enum DecisionTraceSortOrder: string
{
    case CreatedAtAsc = 'createdAt_asc';
    case CreatedAtDesc = 'createdAt_desc';

    public static function fromStringOrDefault(string $value): self
    {
        return self::tryFrom($value) ?? self::CreatedAtDesc;
    }
}
