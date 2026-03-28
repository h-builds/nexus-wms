export type MovementType =
  | "receipt"
  | "putaway"
  | "relocation"
  | "adjustment"
  | "picking"
  | "return_internal";

export type AdjustmentReason =
  | "manual_adjustment"
  | "cycle_count"
  | "incident_damage"
  | "incident_shortage"
  | "quality_hold"
  | "correction";

export interface InventoryMovement {
  id: string;
  productId: string;
  fromLocationId?: string;
  toLocationId?: string;
  type: MovementType;
  quantity: number;
  reason?: AdjustmentReason;
  performedBy: string;
  performedAt: string;
  reference?: string;
  lotNumber?: string;
}
