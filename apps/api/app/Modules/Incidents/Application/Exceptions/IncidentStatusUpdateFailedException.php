<?php

declare(strict_types=1);

namespace App\Modules\Incidents\Application\Exceptions;

use RuntimeException;
use Throwable;

final class IncidentStatusUpdateFailedException extends RuntimeException
{
    public function __construct(string $message = 'Failed to update incident status.', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function dueToReason(string $reason, ?Throwable $previous = null): self
    {
        return new self(sprintf('Failed to update incident status. Reason: %s', $reason), 0, $previous);
    }
}
