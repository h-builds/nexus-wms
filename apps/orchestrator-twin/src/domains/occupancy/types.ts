export type StockStatus = 'available' | 'allocated' | 'blocked' | 'in_transit';

export interface ApiStockItem {
  id: string;
  productId: string;
  locationId: string;
  quantityOnHand: number;
  quantityAvailable: number;
  quantityBlocked: number;
  status: StockStatus;
}

export interface LocationOccupancy {
  locationId: string;
  isOccupied: boolean;
}

export interface ZoneDensity {
  zoneId: string;
  occupiedBins: number;
  totalBins: number;
  densityPercentage: number;
}

export interface OccupancySnapshot {
  locations: Record<string, LocationOccupancy>;
  zoneDensities: ZoneDensity[];
}
