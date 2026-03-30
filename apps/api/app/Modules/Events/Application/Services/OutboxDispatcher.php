<?php

declare(strict_types=1);

namespace App\Modules\Events\Application\Services;

use App\Modules\Events\Infrastructure\Persistence\Eloquent\EventOutboxModel;
use Illuminate\Contracts\Events\Dispatcher;

final class OutboxDispatcher
{
    public function __construct(private readonly Dispatcher $dispatcher)
    {
    }

    /**
     * To be called strictly AFTER a successful database commit.
     */
    public function dispatchAndMark(string $eventId, object $eventInstance): void
    {
        /** @var EventOutboxModel|null $outboxRecord */
        $outboxRecord = EventOutboxModel::where('event_id', $eventId)->first();
        
        if (!$outboxRecord) {
            throw new \RuntimeException(sprintf('Outbox record not found for event ID: %s. Cannot dispatch.', $eventId));
        }

        $this->dispatcher->dispatch($eventInstance);
        $this->dispatcher->dispatch(new BroadcastableOutboxEvent($outboxRecord));

        $outboxRecord->update(['dispatched' => true]);
    }
}
