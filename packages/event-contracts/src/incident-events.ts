import type { DomainEventEnvelope } from "./inventory-events";

// --- Incident Events ---

export interface IncidentReportedPayload {
  incidentId: string;
  productId: string;
  locationId: string;
  type: string;
  severity: string;
  description: string;
  quantityAffected: number;
}

export type IncidentReportedEvent = DomainEventEnvelope<
  "incident.reported",
  IncidentReportedPayload
>;

export interface IncidentStatusUpdatedPayload {
  incidentId: string;
  previousStatus: string;
  newStatus: string;
}

export type IncidentStatusUpdatedEvent = DomainEventEnvelope<
  "incident.status.updated",
  IncidentStatusUpdatedPayload
>;
