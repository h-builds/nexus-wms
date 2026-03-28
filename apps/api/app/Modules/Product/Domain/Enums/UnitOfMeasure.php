<?php

declare(strict_types=1);

namespace App\Modules\Product\Domain\Enums;

enum UnitOfMeasure: string
{
    case UNIT = 'unit';
    case BOX = 'box';
    case PALLET = 'pallet';
    case KILOGRAM = 'kilogram';
    case LITER = 'liter';
}