<?php

declare(strict_types=1);

namespace App\Modules\Events\Infrastructure\Exceptions;

use RuntimeException;
use Throwable;

final class EventOutboxPersistenceFailedException extends RuntimeException
{
    public function __construct(string $message = 'Failed to persist event to outbox.', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
