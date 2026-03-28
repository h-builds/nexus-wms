export type InventoryStatus =
  | "available"
  | "blocked"
  | "in_transit"
  | "quarantine";

export interface StockItem {
  id: string;
  productId: string;
  locationId: string;
  quantityOnHand: number;
  quantityAvailable: number;
  quantityBlocked: number;
  lotNumber?: string;
  serialNumber?: string;
  receivedAt?: string;
  expiresAt?: string;
  status: InventoryStatus;
  version: number;
  updatedAt: string;
}
