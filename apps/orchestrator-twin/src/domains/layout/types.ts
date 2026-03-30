export interface ApiWarehouseLocation {
  id: string;
  warehouseCode: string;
  zone: string;
  aisle: string;
  rack: string;
  level: string;
  bin: string;
  label: string;
  isBlocked: boolean;
}

export interface SpatialBin {
  locationId: string;
  level: string;
  bin: string;
  label: string;
  isBlocked: boolean;
}

export interface SpatialRack {
  id: string;
  bins: SpatialBin[];
}

export interface SpatialAisle {
  id: string;
  racks: SpatialRack[];
}

export interface SpatialZone {
  id: string;
  aisles: SpatialAisle[];
}

export interface SpatialWarehouse {
  warehouseCode: string;
  zones: SpatialZone[];
}

export interface LayoutSnapshot {
  warehouses: SpatialWarehouse[];
}
