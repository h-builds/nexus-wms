/**
 * Re-export all event types from @nexus/event-contracts.
 *
 * This file exists for backward compatibility.
 * New code should import directly from @nexus/event-contracts.
 */
export type {
  DomainEventEnvelope,
  StockAdjustedEvent,
  StockAdjustedPayload,
  StockRelocatedEvent,
  StockReceivedEvent,
  StockPickedEvent,
  StockPutawayEvent,
  StockReturnedEvent,
  IncidentReportedEvent,
  IncidentStatusUpdatedEvent,
  MovementCreatedEvent,
  LocationBlockedEvent,
  LocationUnblockedEvent,
  ProductCreatedEvent,
} from "@nexus-wms/event-contracts";
