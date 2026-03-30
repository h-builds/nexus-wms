export interface RecommendationConfig {
  /** Zone density at which a warning is emitted */
  highOccupancyWarningPct: number;
  /** Zone density at which a critical alert is emitted */
  highOccupancyCriticalPct: number;
  /** Open incidents count at which an inspection is suggested */
  incidentClusterThreshold: number;
  /** Density gap between overloaded and underutilized zones to trigger imbalance */
  imbalanceGapPct: number;
}

export const DEFAULT_RECOMMENDATION_CONFIG: RecommendationConfig = {
  highOccupancyWarningPct: 80,
  highOccupancyCriticalPct: 95,
  incidentClusterThreshold: 2,
  imbalanceGapPct: 40,
};

export type RecommendationSeverity = 'info' | 'warning' | 'critical';

/**
 * Execution priority determines how soon the operator must act.
 *
 * immediate — active system constraint, act now
 * next      — near-future risk, act within current shift
 * monitor   — informational, track during normal operations
 */
export type RecommendationPriority = 'immediate' | 'next' | 'monitor';

export type RecommendationCategory =
  | 'redistribution'
  | 'inspection'
  | 'reallocation'
  | 'escalation'
  | 'imbalance'
  | 'simulation';

/**
 * Each recommendation is an explicit, actionable operational directive.
 * It describes WHAT happened, WHY it matters, and WHAT to do.
 */
export interface Recommendation {
  category: RecommendationCategory;
  severity: RecommendationSeverity;
  priority: RecommendationPriority;
  zoneId: string;
  /** The condition that triggered this recommendation */
  condition: string;
  /** The operational risk if unaddressed */
  risk: string;
  /** A specific, actionable step the operator must take */
  action: string;
}
