<?php

declare(strict_types=1);

namespace App\Modules\Movements\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class InventoryMovementModel extends Model
{
    protected $table = 'inventory_movements';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'product_id',
        'from_location_id',
        'to_location_id',
        'type',
        'quantity',
        'reference',
        'lot_number',
        'reason',
        'performed_by',
        'performed_at',
        'idempotency_key',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'performed_at' => 'datetime',
    ];
}
