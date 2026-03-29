<?php

declare(strict_types=1);

namespace App\Modules\Audit\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string|null $actor_id
 * @property string $actor_type
 * @property string $action
 * @property string $entity_type
 * @property string $entity_id
 * @property array $changeset
 * @property string $correlation_id
 * @property string $timestamp
 */
final class AuditLogModel extends Model
{
    protected $table = 'audit_logs';

    public $incrementing = false;
    protected $keyType = 'string';

    // We do not use Eloquent's updated_at/created_at, we track timestamp explicitly.
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'changeset' => 'array',
        'timestamp' => 'datetime',
    ];
}
