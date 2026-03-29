<?php

declare(strict_types=1);

namespace App\Modules\Events\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class EventOutboxModel extends Model
{
    protected $table = 'event_outbox';

    public $timestamps = true;

    protected $fillable = [
        'event_id',
        'event_type',
        'event_version',
        'occurred_at',
        'actor_id',
        'correlation_id',
        'causation_id',
        'payload',
        'dispatched',
    ];

    protected $casts = [
        'payload' => 'array',
        'dispatched' => 'boolean',
        'occurred_at' => 'datetime',
    ];
}
