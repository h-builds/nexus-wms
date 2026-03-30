import type {
  ApiWarehouseLocation,
  LayoutSnapshot,
  SpatialWarehouse,
  SpatialZone,
  SpatialAisle,
  SpatialRack,
  SpatialBin
} from './types';

/**
 * Transforms a flat list of API location data into a
 * structured, hierarchal spatial graph.
 */
export function mapLocationsToLayoutSnapshot(
  locations: ApiWarehouseLocation[]
): LayoutSnapshot {
  
  const warehousesMap = new Map<string, SpatialWarehouse>();

  for (const warehouseLocation of locations) {
    const warehouseCode = warehouseLocation.warehouseCode || 'DEFAULT_WH';
    const zoneId = warehouseLocation.zone || 'DEFAULT_ZONE';
    const aisleId = warehouseLocation.aisle || 'DEFAULT_AISLE';
    const rackId = warehouseLocation.rack || 'DEFAULT_RACK';

    if (!warehousesMap.has(warehouseCode)) {
      warehousesMap.set(warehouseCode, { warehouseCode, zones: [] });
    }
    const warehouse = warehousesMap.get(warehouseCode)!;

    let zone = warehouse.zones.find(existingZone => existingZone.id === zoneId);
    if (!zone) {
      zone = { id: zoneId, aisles: [] };
      warehouse.zones.push(zone);
    }

    let aisle = zone.aisles.find(existingAisle => existingAisle.id === aisleId);
    if (!aisle) {
      aisle = { id: aisleId, racks: [] };
      zone.aisles.push(aisle);
    }

    let rack = aisle.racks.find(existingRack => existingRack.id === rackId);
    if (!rack) {
      rack = { id: rackId, bins: [] };
      aisle.racks.push(rack);
    }

    const bin: SpatialBin = {
      locationId: warehouseLocation.id,
      level: warehouseLocation.level || '',
      bin: warehouseLocation.bin || '',
      label: warehouseLocation.label || '',
      isBlocked: !!warehouseLocation.isBlocked,
    };
    rack.bins.push(bin);
  }

  return {
    warehouses: Array.from(warehousesMap.values())
  };
}
