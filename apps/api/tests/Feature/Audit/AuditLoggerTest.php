<?php

declare(strict_types=1);

namespace Tests\Feature\Audit;

use App\Modules\Audit\Application\Services\AuditLogger;
use App\Modules\Audit\Domain\Enums\AuditActorType;
use App\Modules\Audit\Infrastructure\Persistence\Eloquent\AuditLogModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class AuditLoggerTest extends TestCase
{
    use RefreshDatabase;

    private AuditLogger $logger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = $this->app->make(AuditLogger::class);
    }

    public function test_it_creates_immutable_audit_log_entry(): void
    {
        $entityId = (string) Str::uuid();
        $actorId = (string) Str::uuid();
        $correlationId = (string) Str::uuid();

        $this->logger->log(
            action: 'test.action',
            entityType: 'TestEntity',
            entityId: $entityId,
            changeset: ['foo' => 'bar'],
            actorId: $actorId,
            actorType: AuditActorType::HUMAN,
            correlationId: $correlationId
        );

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'test.action',
            'entity_type' => 'TestEntity',
            'entity_id' => $entityId,
            'actor_id' => $actorId,
            'actor_type' => 'human',
            'correlation_id' => $correlationId,
        ]);

        $log = AuditLogModel::where('entity_id', $entityId)->first();
        $this->assertNotNull($log->id);
        $this->assertEquals(['foo' => 'bar'], $log->changeset);
        $this->assertNotNull($log->timestamp);
    }

    public function test_it_defaults_to_human_actor_type_and_resolves_correlation_id(): void
    {
        $entityId = (string) Str::uuid();

        $this->logger->log(
            action: 'default.action',
            entityType: 'TestEntity',
            entityId: $entityId,
            changeset: []
        );

        $log = AuditLogModel::where('entity_id', $entityId)->first();
        
        $this->assertEquals('human', $log->actor_type);
        $this->assertNotNull($log->correlation_id);
    }
    
    public function test_it_does_not_silently_fail_on_db_exception(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to persist audit log:');
        
        $longString = str_repeat('A', 255);

        $this->logger->log(
            action: $longString,
            entityType: 'TestEntity',
            entityId: 'id',
            changeset: []
        );
    }
}
