<?php

declare(strict_types=1);

namespace App\Modules\Intelligence\Domain\Exceptions;

use RuntimeException;

final class InvalidAgentDomain extends RuntimeException
{
    public static function withDomain(string $domain): self
    {
        return new self(sprintf('Invalid agent domain: [%s].', $domain));
    }

}
