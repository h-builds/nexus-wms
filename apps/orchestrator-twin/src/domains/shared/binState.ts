/**
 * Resolved visual state for a single bin cell.
 * Computed at the composition layer (WarehouseGrid), never inside BinCell.
 *
 * Priority order (highest wins):
 * 1. blocked
 * 2. incident
 * 3. occupied
 * 4. empty
 */
export type BinVisualState = 'empty' | 'occupied' | 'incident' | 'blocked';

import type { IncidentSeverity } from '../incidents/types';

export interface BinOverlay {
  state: BinVisualState;
  incidentCount: number;
  highestSeverity: IncidentSeverity | null;
  /** Zone-level density percentage (0–100). Used for height mapping in 2.5D rendering. */
  densityPct: number;
}

/**
 * Resolves the final visual state for a bin using strict priority precedence.
 * This is a pure function with no side effects.
 */
export function resolveBinState(
  isBlocked: boolean,
  incidentCount: number,
  isOccupied: boolean,
): BinVisualState {
  if (isBlocked) return 'blocked';
  if (incidentCount > 0) return 'incident';
  if (isOccupied) return 'occupied';
  return 'empty';
}
