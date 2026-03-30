import type {
  SimulationInput,
  SimulationResult,
  SimulationAllocation,
} from './types';
import type { LayoutSnapshot } from '../layout/types';
import type { OccupancySnapshot } from '../occupancy/types';

/**
 * Deterministic simulation engine.
 *
 * Given an inbound unit count, finds empty, non-blocked locations
 * and allocates sequentially (first-fit). No backend mutation,
 * no persistence, no randomness.
 */
export class SimulationService {
  /**
   * Simulates placing `input.units` into the first available empty bins.
   * Returns the allocation plan and which zones are affected.
   */
  simulate(
    input: SimulationInput,
    layout: LayoutSnapshot,
    occupancy: OccupancySnapshot,
  ): SimulationResult {
    const allocations: SimulationAllocation[] = [];
    let remainingUnits = input.units;

    for (const warehouse of layout.warehouses) {
      if (remainingUnits <= 0) break;

      for (const zone of warehouse.zones) {
        if (remainingUnits <= 0) break;

        for (const aisle of zone.aisles) {
          if (remainingUnits <= 0) break;

          for (const rack of aisle.racks) {
            if (remainingUnits <= 0) break;

            for (const bin of rack.bins) {
              if (remainingUnits <= 0) break;

              if (bin.isBlocked) continue;

              const locationOccupancy = occupancy.locations[bin.locationId];
              if (locationOccupancy?.isOccupied) continue;

              allocations.push({
                locationId: bin.locationId,
                zoneId: zone.id,
                label: bin.label,
              });
              remainingUnits--;
            }
          }
        }
      }
    }

    const affectedZones = [...new Set(allocations.map(allocation => allocation.zoneId))];

    return {
      totalUnitsAllocated: allocations.length,
      totalUnitsUnplaced: Math.max(remainingUnits, 0),
      allocations,
      affectedZones,
    };
  }
}
