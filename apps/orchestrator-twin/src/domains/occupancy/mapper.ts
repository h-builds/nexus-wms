import type { ApiStockItem, OccupancySnapshot, LocationOccupancy, ZoneDensity } from './types';
import type { LayoutSnapshot } from '../layout/types';

/**
 * Transforms flat stock items into an OccupancySnapshot.
 * Requires the structural LayoutSnapshot to correlate stock to physical zones
 * and calculate zone density.
 */
export function mapStockToOccupancySnapshot(
  stockItems: ApiStockItem[],
  layout: LayoutSnapshot
): OccupancySnapshot {
  
  // 1. Group stock quantities by locationId. A location can hold multiple identical or different stock entries.
  const locationQuantities = new Map<string, number>();
  
  for (const stockItem of stockItems) {
    if (!stockItem.locationId) continue;
    const currentQuantity = locationQuantities.get(stockItem.locationId) || 0;
    locationQuantities.set(stockItem.locationId, currentQuantity + stockItem.quantityOnHand);
  }

  const locations: Record<string, LocationOccupancy> = {};
  for (const [locationId, sumQuantity] of locationQuantities.entries()) {
    locations[locationId] = {
      locationId: locationId,
      isOccupied: sumQuantity > 0,
    };
  }

  const zoneDensities: ZoneDensity[] = [];
  
  for (const warehouse of layout.warehouses) {
    for (const zone of warehouse.zones) {
      let totalBins = 0;
      let occupiedBins = 0;

      for (const aisle of zone.aisles) {
        for (const rack of aisle.racks) {
          for (const bin of rack.bins) {
            totalBins++;
            if (locations[bin.locationId]?.isOccupied) {
              occupiedBins++;
            }
          }
        }
      }

      zoneDensities.push({
        zoneId: zone.id,
        totalBins,
        occupiedBins,
        densityPercentage: totalBins > 0 ? (occupiedBins / totalBins) * 100 : 0
      });
    }
  }

  return {
    locations,
    zoneDensities
  };
}
