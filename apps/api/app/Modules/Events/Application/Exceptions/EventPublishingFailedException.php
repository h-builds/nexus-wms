<?php

declare(strict_types=1);

namespace App\Modules\Events\Application\Exceptions;

use RuntimeException;
use Throwable;

final class EventPublishingFailedException extends RuntimeException
{
    public readonly string $eventType;
    public readonly ?string $eventId;

    public function __construct(
        string $eventType,
        ?string $eventId = null,
        ?Throwable $previous = null
    ) {
        $this->eventType = $eventType;
        $this->eventId = $eventId;
        
        $message = $eventId 
            ? sprintf('Failed to publish event [%s] of type [%s].', $eventId, $eventType)
            : sprintf('Failed to publish event of type [%s].', $eventType);
            
        parent::__construct($message, 0, $previous);
    }
}
