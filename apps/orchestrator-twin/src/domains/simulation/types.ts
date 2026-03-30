export interface SimulationInput {
  units: number;
}

export interface SimulationAllocation {
  locationId: string;
  zoneId: string;
  label: string;
}

export interface SimulationResult {
  totalUnitsAllocated: number;
  totalUnitsUnplaced: number;
  allocations: SimulationAllocation[];
  affectedZones: string[];
}
