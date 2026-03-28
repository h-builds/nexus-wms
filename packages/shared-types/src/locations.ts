export interface WarehouseLocation {
  id: string;
  warehouseCode: string;
  zone?: string;
  aisle?: string;
  rack?: string;
  level?: string;
  bin?: string;
  label: string;
  isBlocked: boolean;
}
