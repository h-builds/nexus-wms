<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\DTOs;

use App\Modules\Inventory\Domain\Enums\MutationOperation;

final readonly class StockMutationDTO
{
    public function __construct(
        public MutationOperation $operation,
        public string $productId,
        public int $quantity,
        public ?string $fromLocationId = null,
        public ?string $toLocationId = null,
        public ?string $lotNumber = null,
    ) {}
}
