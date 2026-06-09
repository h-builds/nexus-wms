<?php

declare(strict_types=1);

namespace App\Modules\Intelligence\Application\Agents;

use App\Modules\Intelligence\Domain\Entities\DecisionTrace;

/**
 * Contract for deterministic rule-based agents.
 *
 * Agents receive a canonical event and optionally produce a DecisionTrace.
 * They MUST be:
 *   - deterministic (same input → same output)
 *   - side-effect-free (no state mutation, no event emission, no I/O)
 *   - synchronous (no async, no queues)
 */
interface DecisionAgent
{
    public function evaluate(CanonicalEvent $event): ?DecisionTrace;
}
