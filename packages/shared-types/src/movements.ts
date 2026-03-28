export type MovementType =
  | "receipt"
  | "putaway"
  | "relocation"
  | "adjustment"
  | "picking"
  | "return_internal";

export interface InventoryMovement {
  id: string;
  productId: string;
  fromLocationId?: string;
  toLocationId?: string;
  type: MovementType;
  quantity: number;
  performedBy: string;
  performedAt: string;
  reference?: string;
}
