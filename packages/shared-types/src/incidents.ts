export type IncidentType =
  | "damage"
  | "shortage"
  | "overage"
  | "expiration"
  | "misplacement"
  | "broken_packaging"
  | "nonconforming_product"
  | "picking_blocker"
  | "lot_error";

export type IncidentStatus = "open" | "in_review" | "resolved" | "closed";

export type IncidentSeverity = "low" | "medium" | "high";

export interface InventoryIncident {
  id: string;
  productId: string;
  locationId: string;
  type: IncidentType;
  severity: IncidentSeverity;
  description: string;
  quantityAffected: number;
  status: IncidentStatus;
  reportedBy: string;
  createdAt: string;
  updatedAt: string;
}
