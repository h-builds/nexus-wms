<?php

declare(strict_types=1);

namespace App\Modules\Incidents\Application\Exceptions;

use RuntimeException;

final class IdempotencyConflictException extends RuntimeException
{
    public function __construct(string $message = 'Idempotency key already processed.')
    {
        parent::__construct($message, 409);
    }
}
