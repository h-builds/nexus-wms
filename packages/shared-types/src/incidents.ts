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

export interface InventoryIncident {
  id: string;
  productId: string;
  locationId: string;
  type: IncidentType;
  description: string;
  status: "open" | "in_review" | "resolved" | "closed";
  reportedBy: string;
  createdAt: string;
}
