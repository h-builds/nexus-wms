<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class StockItemModel extends Model
{
    protected $table = 'stock_items';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'product_id',
        'location_id',
        'quantity_on_hand',
        'quantity_available',
        'quantity_blocked',
        'lot_number',
        'serial_number',
        'received_at',
        'expires_at',
        'status',
        'version',
    ];

    protected $casts = [
        'quantity_on_hand' => 'integer',
        'quantity_available' => 'integer',
        'quantity_blocked' => 'integer',
        'version' => 'integer',
        'received_at' => 'datetime',
        'expires_at' => 'datetime',
    ];
}
