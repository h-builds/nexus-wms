<?php

declare(strict_types=1);

namespace App\Modules\Incidents\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class InventoryIncidentModel extends Model
{
    protected $table = 'inventory_incidents';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'product_id',
        'location_id',
        'type',
        'severity',
        'status',
        'description',
        'quantity_affected',
        'reported_by',
        'assigned_to',
        'notes',
        'created_at',
        'updated_at',
        'idempotency_key',
    ];

    protected $casts = [
        'quantity_affected' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
