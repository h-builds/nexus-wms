<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Domain\Enums;

enum MutationOperation: string
{
    case ADD = 'add';
    case SUBTRACT = 'subtract';
    case MOVE = 'move';
}
