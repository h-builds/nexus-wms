import type { DomainEventEnvelope } from "./inventory-events";

// --- Movement Events ---

export interface MovementCreatedPayload {
  movementId: string;
  productId: string;
  type: string;
  quantity: number;
  fromLocationId?: string;
  toLocationId?: string;
}

export type MovementCreatedEvent = DomainEventEnvelope<
  "movement.created",
  MovementCreatedPayload
>;

// --- Location Events ---

export interface LocationBlockedPayload {
  locationId: string;
  reason: string;
}

export type LocationBlockedEvent = DomainEventEnvelope<
  "location.blocked",
  LocationBlockedPayload
>;

export interface LocationUnblockedPayload {
  locationId: string;
}

export type LocationUnblockedEvent = DomainEventEnvelope<
  "location.unblocked",
  LocationUnblockedPayload
>;

// --- Product Events ---

export interface ProductCreatedPayload {
  productId: string;
  sku: string;
  name: string;
}

export type ProductCreatedEvent = DomainEventEnvelope<
  "product.created",
  ProductCreatedPayload
>;
