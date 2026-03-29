<?php

declare(strict_types=1);

namespace App\Modules\Audit\Application\Services;

use App\Modules\Audit\Domain\Enums\AuditActorType;
use App\Modules\Audit\Infrastructure\Persistence\Eloquent\AuditLogModel;
use Illuminate\Support\Str;

final class AuditLogger
{
    /**
     * @param array<string, mixed> $changeset Must exclude sensitive values
     * @param string|null $actorId Must be extracted from trusted authenticated context, never raw user input
     * @throws \RuntimeException If persistence fails, to enforce strict audit traceability
     */
    public function log(
        string $action,
        string $entityType,
        string $entityId,
        array $changeset,
        ?string $actorId = null,
        AuditActorType $actorType = AuditActorType::HUMAN,
        ?string $correlationId = null
    ): void {
        $resolvedCorrelationId = $correlationId ?? request()->header('X-Correlation-ID', (string) Str::uuid());

        try {
            AuditLogModel::create([
                'id' => (string) Str::uuid(),
                'actor_id' => $actorId,
                'actor_type' => $actorType->value,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'changeset' => $changeset,
                'correlation_id' => $resolvedCorrelationId,
                'timestamp' => now(),
            ]);
        } catch (\Throwable $e) {
            // Audit MUST persist if state change persisted. Transaction aborts if this throws.
            throw new \RuntimeException('Failed to persist audit log: ' . $e->getMessage(), 0, $e);
        }
    }
}
