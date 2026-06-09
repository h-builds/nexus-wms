<?php

declare(strict_types=1);

namespace App\Modules\Intelligence\Domain\Exceptions;

use RuntimeException;

final class InvalidStateTransitionException extends RuntimeException
{
    public function __construct(string $message)
    {
        parent::__construct($message, 422);
    }
}
