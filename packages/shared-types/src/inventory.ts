export type InventoryStatus =
  | "available"
  | "blocked"
  | "in_transit"
  | "quarantine";

export interface StockItem {
  id: string;
  sku: string;
  productName: string;
  locationId: string;
  quantityOnHand: number;
  quantityAvailable: number;
  quantityBlocked: number;
  lotNumber?: string;
  serialNumber?: string;
  receivedAt?: string;
  expiresAt?: string;
  status: InventoryStatus;
}
