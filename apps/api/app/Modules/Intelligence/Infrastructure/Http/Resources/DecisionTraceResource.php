<?php

declare(strict_types=1);

namespace App\Modules\Intelligence\Infrastructure\Http\Resources;

use App\Modules\Intelligence\Domain\Entities\DecisionTrace;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class DecisionTraceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $decisionTrace = $this->resource;

        return [
            'id' => $decisionTrace->id(),
            'traceType' => $decisionTrace->traceType()->value,
            'agentId' => $decisionTrace->agentId(),
            'agentDomain' => $decisionTrace->agentDomain()->value,
            'detection' => $decisionTrace->detection(),
            'reasoning' => $decisionTrace->reasoning(),
            'suggestion' => $decisionTrace->suggestion(),
            'severity' => $decisionTrace->severity()->value,
            'causationId' => $decisionTrace->causationId(),
            'correlationId' => $decisionTrace->correlationId(),
            'triggerEventIds' => $decisionTrace->triggerEventIds(),
            'status' => $decisionTrace->status()->value,
            'actedUponAt' => $decisionTrace->actedUponAt(),
            'actedUponBy' => $decisionTrace->actedUponBy(),
            'createdAt' => $decisionTrace->createdAt(),
            'updatedAt' => $decisionTrace->updatedAt(),
        ];
    }
}
