export interface InventoryAdjustedEvent {
  type: "inventory.adjusted";
  payload: {
    productId: string;
    locationId: string;
    previousQuantity: number;
    newQuantity: number;
    reason: string;
    actorId: string;
    occurredAt: string;
  };
}
