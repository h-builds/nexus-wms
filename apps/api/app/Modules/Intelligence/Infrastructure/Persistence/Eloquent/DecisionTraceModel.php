<?php

declare(strict_types=1);

namespace App\Modules\Intelligence\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $trace_type
 * @property string $agent_id
 * @property string $agent_domain
 * @property string $detection
 * @property string $reasoning
 * @property string $suggestion
 * @property string $severity
 * @property string $causation_id
 * @property string $correlation_id
 * @property array $trigger_event_ids
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $acted_upon_at
 * @property string|null $acted_upon_by
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
final class DecisionTraceModel extends Model
{
    protected $table = 'decision_traces';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'trace_type',
        'agent_id',
        'agent_domain',
        'detection',
        'reasoning',
        'suggestion',
        'severity',
        'causation_id',
        'correlation_id',
        'trigger_event_ids',
        'status',
        'acted_upon_at',
        'acted_upon_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'trigger_event_ids' => 'array',
            'acted_upon_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
