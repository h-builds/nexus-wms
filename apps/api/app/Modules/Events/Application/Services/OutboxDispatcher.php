<?php

declare(strict_types=1);

namespace App\Modules\Events\Application\Services;

use App\Modules\Events\Infrastructure\Persistence\Eloquent\EventOutboxModel;
use Illuminate\Support\Facades\Event;

final class OutboxDispatcher
{
    /**
     * Dispatch synchronously and mark as dispatched.
     * To be called strictly AFTER a successful database commit.
     * 
     * @param string $eventId The ID of the event in the outbox.
     * @param object $eventInstance The actual Domain Event instance to trigger in Laravel's bus.
     */
    public function dispatchAndMark(string $eventId, object $eventInstance): void
    {
        Event::dispatch($eventInstance);
        EventOutboxModel::where('event_id', $eventId)->update(['dispatched' => true]);
    }
}
