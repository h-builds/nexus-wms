<?php

declare(strict_types=1);

namespace App\Modules\Locations\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class LocationModel extends Model
{
    protected $table = 'locations';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'warehouse_code',
        'zone',
        'aisle',
        'rack',
        'level',
        'bin',
        'label',
        'is_blocked',
    ];

    protected $casts = [
        'is_blocked' => 'boolean',
    ];
}
