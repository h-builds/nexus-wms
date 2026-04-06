<?php

declare(strict_types=1);

namespace App\Modules\Intelligence\Domain\Enums;

use App\Modules\Intelligence\Domain\Exceptions\InvalidTraceType;

enum TraceType: string
{
    case AnomalyDetection = 'anomaly_detection';
    case PatternInsight = 'pattern_insight';
    case OptimizationSuggestion = 'optimization_suggestion';
    case RiskAlert = 'risk_alert';

    public static function fromValue(string $value): self
    {
        $enum = self::tryFrom($value);

        if ($enum === null) {
            throw InvalidTraceType::withType($value);
        }

        return $enum;
    }
}
