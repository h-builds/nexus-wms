<?php

declare(strict_types=1);

namespace Tests\Feature\Outbox;

use App\Modules\Events\Application\Services\BroadcastableOutboxEvent;
use App\Modules\Events\Application\Services\OutboxDispatcher;
use App\Modules\Events\Infrastructure\Persistence\Eloquent\EventOutboxModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

class OutboxDispatcherTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatcher_executes_only_once_for_same_event_id(): void
    {
        Event::fake([BroadcastableOutboxEvent::class]);

        $eventId = Str::uuid()->toString();
        $outboxEntry = EventOutboxModel::create([
            'event_id' => $eventId,
            'event_type' => 'test.event',
            'event_version' => 1,
            'occurred_at' => now(),
            'actor_id' => 'system',
            'correlation_id' => Str::uuid()->toString(),
            'causation_id' => $eventId,
            'payload' => ['foo' => 'bar'],
            'dispatched' => false,
        ]);

        $outboxDispatcher = $this->app->make(OutboxDispatcher::class);

        $outboxDispatcher->dispatchAndMark($eventId, new \stdClass());
        Event::assertDispatchedTimes(BroadcastableOutboxEvent::class, 1);

        $outboxEntry->refresh();
        $this->assertTrue($outboxEntry->dispatched);
        $this->assertNotNull($outboxEntry->dispatched_at);

        $outboxDispatcher->dispatchAndMark($eventId, new \stdClass());
        Event::assertDispatchedTimes(BroadcastableOutboxEvent::class, 1);
    }

    public function test_dispatcher_fails_silently_if_no_record_exists(): void
    {
        Event::fake([BroadcastableOutboxEvent::class]);

        $outboxDispatcher = $this->app->make(OutboxDispatcher::class);
        $outboxDispatcher->dispatchAndMark('fake-id', new \stdClass());

        Event::assertNotDispatched(BroadcastableOutboxEvent::class);
    }

    public function test_broadcastable_event_formats_canonical_payload(): void
    {
        $eventId = Str::uuid()->toString();
        $correlationId = Str::uuid()->toString();
        $causationId = Str::uuid()->toString();
        
        $outboxEntry = new EventOutboxModel([
            'event_id' => $eventId,
            'event_type' => 'test.event',
            'event_version' => 1,
            'occurred_at' => now(),
            'actor_id' => 'system',
            'correlation_id' => $correlationId,
            'causation_id' => $causationId,
            'payload' => ['foo' => 'bar'],
            'dispatched' => false,
        ]);

        $broadcastable = new BroadcastableOutboxEvent($outboxEntry);
        $eventPayload = $broadcastable->broadcastWith();

        $this->assertArrayHasKey('eventId', $eventPayload);
        $this->assertEquals($eventId, $eventPayload['eventId']);
        
        $this->assertArrayHasKey('eventType', $eventPayload);
        $this->assertEquals('test.event', $eventPayload['eventType']);
        
        $this->assertArrayHasKey('eventVersion', $eventPayload);
        $this->assertEquals(1, $eventPayload['eventVersion']);
        
        $this->assertArrayHasKey('occurredAt', $eventPayload);
        $this->assertIsString($eventPayload['occurredAt']);
        $this->assertStringContainsString('T', $eventPayload['occurredAt']);
        $this->assertStringContainsString('Z', $eventPayload['occurredAt']);
        
        $this->assertArrayHasKey('actorId', $eventPayload);
        $this->assertEquals('system', $eventPayload['actorId']);
        
        $this->assertArrayHasKey('correlationId', $eventPayload);
        $this->assertEquals($correlationId, $eventPayload['correlationId']);
        
        $this->assertArrayHasKey('causationId', $eventPayload);
        $this->assertEquals($causationId, $eventPayload['causationId']);
        
        $this->assertArrayHasKey('payload', $eventPayload);
        $this->assertEquals(['foo' => 'bar'], $eventPayload['payload']);
    }
}
