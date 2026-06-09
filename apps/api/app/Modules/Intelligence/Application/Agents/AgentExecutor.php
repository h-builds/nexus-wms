<?php

declare(strict_types=1);

namespace App\Modules\Intelligence\Application\Agents;

use App\Modules\Intelligence\Domain\Repositories\DecisionTraceRepository;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Synchronously executes all registered agents against a canonical event.
 *
 * This is a side-effect executor — it runs AFTER the event pipeline completes,
 * outside the domain transaction. It does not alter events nor emit new ones.
 */
final class AgentExecutor
{
    /** @var DecisionAgent[] */
    private readonly array $agents;

    /**
     * @param DecisionAgent[] $agents
     */
    public function __construct(
        array $agents,
        private readonly DecisionTraceRepository $repository,
    ) {
        $this->agents = $agents;
    }

    public function evaluate(CanonicalEvent $event): void
    {
        foreach ($this->agents as $agent) {
            $this->evaluateAgent($agent, $event);
        }
    }

    private function evaluateAgent(DecisionAgent $agent, CanonicalEvent $event): void
    {
        try {
            $trace = $agent->handle($event);

            if ($trace === null) {
                return;
            }

            if ($this->repository->existsByCausationId($trace->causationId(), $trace->agentId())) {
                return;
            }

            $this->repository->save($trace);
        } catch (Throwable $e) {
            Log::error('Agent execution failed', [
                'eventId' => $event->eventId,
                'agentId' => get_class($agent),
                'errorMessage' => $e->getMessage(),
            ]);
        }
    }
}
