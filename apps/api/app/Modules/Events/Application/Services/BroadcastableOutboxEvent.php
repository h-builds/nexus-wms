<?php

declare(strict_types=1);

namespace App\Modules\Events\Application\Services;

use App\Modules\Events\Infrastructure\Persistence\Eloquent\EventOutboxModel;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

final class BroadcastableOutboxEvent implements ShouldBroadcastNow
{
    public function __construct(public readonly EventOutboxModel $outboxEvent) {}

    public function broadcastOn(): array
    {
        $eventTypeSegments = explode('.', $this->outboxEvent->event_type);
        $domain = $eventTypeSegments[0] ?? 'general';

        return [
            new Channel($domain),
            new Channel('warehouse.monitoring'),
        ];
    }

    public function broadcastAs(): string
    {
        return $this->outboxEvent->event_type;
    }

    public function broadcastWith(): array
    {
        return [
            'eventId' => $this->outboxEvent->event_id,
            'eventType' => $this->outboxEvent->event_type,
            'eventVersion' => $this->outboxEvent->event_version,
            'occurredAt' => $this->outboxEvent->occurred_at instanceof \DateTimeInterface 
                ? $this->outboxEvent->occurred_at->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s.u\Z')
                : $this->outboxEvent->occurred_at,
            'actorId' => $this->outboxEvent->actor_id,
            'correlationId' => $this->outboxEvent->correlation_id,
            'causationId' => $this->outboxEvent->causation_id,
            'payload' => $this->outboxEvent->payload,
        ];
    }
}
