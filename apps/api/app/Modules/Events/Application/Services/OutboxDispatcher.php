<?php

declare(strict_types=1);

namespace App\Modules\Events\Application\Services;

use App\Modules\Events\Infrastructure\Persistence\Eloquent\EventOutboxModel;
use Illuminate\Contracts\Events\Dispatcher;
use Psr\Log\LoggerInterface;

final class OutboxDispatcher
{
    public function __construct(
        private readonly Dispatcher $dispatcher,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * To be called strictly AFTER a successful database commit.
     */
    public function dispatchAndMark(string $eventId, object $eventInstance): void
    {
        /** @var EventOutboxModel|null $outboxRecord */
        $outboxRecord = EventOutboxModel::where('event_id', $eventId)->first();
        
        if (!$outboxRecord) {
            $this->logger->debug('OutboxDispatcher: Aborted dispatch; event record vanished (likely transaction rollback).', [
                'event_id' => $eventId,
                'event_class' => get_class($eventInstance)
            ]);
            return;
        }

        // Atomic lock/mark for At-Most-Once delivery
        $updated = EventOutboxModel::where('event_id', $eventId)
            ->where('dispatched', false)
            ->update([
                'dispatched' => true,
                'dispatched_at' => now(),
            ]);

        if ($updated === 0) {
            $this->logger->debug('OutboxDispatcher: Skipped dispatch; event already marked as dispatched idempotently.', [
                'event_id' => $eventId,
            ]);
            return;
        }

        // Technically we mutate the in-memory object so BroadcastableOutboxEvent gets accurate status
        $outboxRecord->dispatched = true;
        $outboxRecord->dispatched_at = now();

        $this->dispatcher->dispatch($eventInstance);
        $this->dispatcher->dispatch(new BroadcastableOutboxEvent($outboxRecord));
    }
}
