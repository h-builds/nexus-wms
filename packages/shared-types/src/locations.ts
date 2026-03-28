export interface WarehouseLocation {
  id: string;
  warehouseId: string;
  zone?: string;
  aisle?: string;
  rack?: string;
  level?: string;
  bin?: string;
  label: string;
  isBlocked: boolean;
}
