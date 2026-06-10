import type { CanonicalEvent } from '../stores/useEventIngestionStore'

export interface DriftedEvent {
    eventId: string;
    eventType: string;
    locationId: string;
    expectedQuantity: number;
    localQuantity: number;
    attemptedNewQuantity: number;
    occurredAt: string;
}

export interface InterpretedState {
    inventoryByLocation: Record<string, number>;
    openIncidents: Set<string>;
    openIncidentsByLocation: Record<string, Set<string>>;
    totalMovementsProcessed: number;
    processedEventIds: Set<string>;
    lastProcessedEventTime: string | null;
    driftedEvents: DriftedEvent[];
}

type Transformer = (event: CanonicalEvent, state: InterpretedState) => void;

export class EventInterpreter {
    private handlers: Record<string, Transformer> = {};

    constructor() {
        this.registerHandlers();
    }

    private registerHandlers() {
        this.handlers['.inventory.stock.adjusted'] = this.handleStockAdjusted;
        this.handlers['.inventory.stock.received'] = this.handleStockReceived;
        this.handlers['.inventory.stock.picked'] = this.handleStockPicked;
        this.handlers['.inventory.stock.relocated'] = this.handleStockRelocated;
        this.handlers['.incident.reported'] = this.handleIncidentReported;
        this.handlers['.incident.status.updated'] = this.handleIncidentStatusUpdated;
        this.handlers['.movement.created'] = this.handleMovementCreated;
    }

    public interpret(event: CanonicalEvent, state: InterpretedState): void {
        if (state.processedEventIds.has(event.eventId)) {
            console.warn(`[EventInterpreter] Ignoring duplicate event: ${event.eventId}`);
            return;
        }

        const handler = this.handlers[event.eventType];
        if (handler) {
            handler.call(this, event, state);
        } else {
            console.debug(`[EventInterpreter] No handler for event: ${event.eventType}`);
        }

        state.processedEventIds.add(event.eventId);
        state.lastProcessedEventTime = event.occurredAt;
    }

    private handleStockAdjusted(event: CanonicalEvent, state: InterpretedState): void {
        const payload = event.payload as { locationId?: string, newQuantity?: number, previousQuantity?: number };
        const { locationId, newQuantity, previousQuantity } = payload;
        if (!locationId) return;

        const localQuantity = state.inventoryByLocation[locationId] ?? 0;
        const expectedQuantity = previousQuantity ?? 0;

        if (localQuantity !== expectedQuantity) {
            state.driftedEvents.push({
                eventId: event.eventId,
                eventType: event.eventType,
                locationId,
                expectedQuantity,
                localQuantity,
                attemptedNewQuantity: newQuantity ?? 0,
                occurredAt: event.occurredAt,
            });
            console.warn(
                `[EventInterpreter] Drift detected for ${event.eventId}: ` +
                `location=${locationId} local=${localQuantity} expected=${expectedQuantity}. ` +
                `Refusing stale adjustment.`
            );
            return;
        }

        state.inventoryByLocation[locationId] = newQuantity ?? 0;
    }

    private handleStockReceived(event: CanonicalEvent, state: InterpretedState): void {
        const payload = event.payload as { locationId?: string, quantity: number };
        const { locationId, quantity } = payload;
        if (locationId) {
            const current = state.inventoryByLocation[locationId] || 0;
            state.inventoryByLocation[locationId] = current + quantity;
        }
    }

    private handleStockPicked(event: CanonicalEvent, state: InterpretedState): void {
        const payload = event.payload as { locationId?: string, quantity: number };
        const { locationId, quantity } = payload;
        if (locationId) {
            const current = state.inventoryByLocation[locationId] || 0;
            state.inventoryByLocation[locationId] = Math.max(0, current - quantity);
        }
    }

    private handleStockRelocated(event: CanonicalEvent, state: InterpretedState): void {
        const payload = event.payload as { fromLocationId?: string, toLocationId?: string, quantity: number };
        const { fromLocationId, toLocationId, quantity } = payload;
        
        if (fromLocationId) {
            const currentFrom = state.inventoryByLocation[fromLocationId] || 0;
            state.inventoryByLocation[fromLocationId] = Math.max(0, currentFrom - quantity);
        }
        
        if (toLocationId) {
            const currentTo = state.inventoryByLocation[toLocationId] || 0;
            state.inventoryByLocation[toLocationId] = currentTo + quantity;
        }
    }

    private handleIncidentReported(event: CanonicalEvent, state: InterpretedState): void {
        const payload = event.payload as { incidentId?: string, locationId?: string };
        const { incidentId, locationId } = payload;
        if (incidentId) {
            state.openIncidents.add(incidentId);
            if (locationId) {
                if (!state.openIncidentsByLocation[locationId]) {
                    state.openIncidentsByLocation[locationId] = new Set<string>();
                }
                state.openIncidentsByLocation[locationId].add(incidentId);
            }
        }
    }

    private handleIncidentStatusUpdated(event: CanonicalEvent, state: InterpretedState): void {
        const payload = event.payload as { incidentId?: string, newStatus: string, locationId?: string };
        const { incidentId, newStatus, locationId } = payload;
        if (incidentId) {
            const isOpen = ['open', 'OPEN'].includes(newStatus);
            if (isOpen) {
                state.openIncidents.add(incidentId);
                // Status updates might not carry locationId if the domain doesn't pass it,
                // but if they do, ensure we track it.
                if (locationId) {
                    if (!state.openIncidentsByLocation[locationId]) {
                        state.openIncidentsByLocation[locationId] = new Set<string>();
                    }
                    state.openIncidentsByLocation[locationId].add(incidentId);
                }
            } else {
                state.openIncidents.delete(incidentId);
                for (const key of Object.keys(state.openIncidentsByLocation)) {
                    state.openIncidentsByLocation[key].delete(incidentId);
                }
            }
        }
    }

    private handleMovementCreated(event: CanonicalEvent, state: InterpretedState): void {
        state.totalMovementsProcessed += 1;
    }
}
