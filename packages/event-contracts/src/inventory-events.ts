/**
 * Base event envelope shared by all domain events.
 * All events are immutable facts emitted after successful state changes.
 */
export interface DomainEventEnvelope<T extends string, P> {
  eventId: string;
  eventType: T;
  eventVersion: number;
  occurredAt: string;
  actorId: string;
  correlationId: string;
  causationId: string;
  payload: P;
}

// --- Inventory Events ---

export interface StockAdjustedPayload {
  productId: string;
  locationId: string;
  previousQuantity: number;
  newQuantity: number;
  reason: string;
}

export type StockAdjustedEvent = DomainEventEnvelope<
  "inventory.stock.adjusted",
  StockAdjustedPayload
>;

export interface StockRelocatedPayload {
  productId: string;
  fromLocationId: string;
  toLocationId: string;
  quantity: number;
}

export type StockRelocatedEvent = DomainEventEnvelope<
  "inventory.stock.relocated",
  StockRelocatedPayload
>;

export interface StockReceivedPayload {
  movementId: string;
  productId: string;
  locationId: string;
  quantity: number;
  lotNumber?: string;
}

export type StockReceivedEvent = DomainEventEnvelope<
  "inventory.stock.received",
  StockReceivedPayload
>;

export interface StockPickedPayload {
  productId: string;
  locationId: string;
  quantity: number;
}

export type StockPickedEvent = DomainEventEnvelope<
  "inventory.stock.picked",
  StockPickedPayload
>;

export interface StockPutawayPayload {
  productId: string;
  fromLocationId: string;
  toLocationId: string;
  quantity: number;
}

export type StockPutawayEvent = DomainEventEnvelope<
  "inventory.stock.putaway",
  StockPutawayPayload
>;

export interface StockReturnedPayload {
  productId: string;
  fromLocationId: string;
  toLocationId: string;
  quantity: number;
}

export type StockReturnedEvent = DomainEventEnvelope<
  "inventory.stock.returned",
  StockReturnedPayload
>;
