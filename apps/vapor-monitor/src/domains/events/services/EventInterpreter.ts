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

function safeGet<V>(record: Record<string, V>, key: string, fallback: V): V {
    const descriptor = Object.getOwnPropertyDescriptor(record, key);
    return descriptor !== undefined ? (descriptor.value as V) : fallback;
}

function safeSet<V>(record: Record<string, V>, key: string, value: V): void {
    if (key === '__proto__' || key === 'constructor' || key === 'prototype') {
        console.warn(`[EventInterpreter] Refusing to set dangerous key: ${key}`);
        return;
    }
    Object.defineProperty(record, key, {
        value,
        writable: true,
        enumerable: true,
        configurable: true,
    });
}

export class EventInterpreter {
    private handlers: Map<string, Transformer> = new Map();

    constructor() {
        this.registerHandlers();
    }

    private registerHandlers() {
        this.handlers.set('.inventory.stock.adjusted', this.handleStockAdjusted);
        this.handlers.set('.inventory.stock.received', this.handleStockReceived);
        this.handlers.set('.inventory.stock.picked', this.handleStockPicked);
        this.handlers.set('.inventory.stock.relocated', this.handleStockRelocated);
        this.handlers.set('.incident.reported', this.handleIncidentReported);
        this.handlers.set('.incident.status.updated', this.handleIncidentStatusUpdated);
        this.handlers.set('.movement.created', this.handleMovementCreated);
    }

    public interpret(event: CanonicalEvent, state: InterpretedState): void {
        if (state.processedEventIds.has(event.eventId)) {
            console.warn(`[EventInterpreter] Ignoring duplicate event: ${event.eventId}`);
            return;
        }

        const handler = this.handlers.get(event.eventType);
        if (handler) {
            handler.call(this, event, state);
        } else {
            console.debug(`[EventInterpreter] No handler for event: ${event.eventType}`);
        }

        state.processedEventIds.add(event.eventId);
        state.lastProcessedEventTime = event.occurredAt;
    }

    private handleStockAdjusted(event: CanonicalEvent, state: InterpretedState): void {
        const payload = event.payload as { locationId?: string; newQuantity?: number; previousQuantity?: number };
        const { locationId, newQuantity, previousQuantity } = payload;
        if (!locationId) return;

        const localQuantity = safeGet(state.inventoryByLocation, locationId, 0);
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

        safeSet(state.inventoryByLocation, locationId, newQuantity ?? 0);
    }

    private handleStockReceived(event: CanonicalEvent, state: InterpretedState): void {
        const payload = event.payload as { locationId?: string; quantity?: number };
        const { locationId, quantity } = payload;
        if (locationId && quantity !== undefined) {
            const currentQuantity = safeGet(state.inventoryByLocation, locationId, 0);
            safeSet(state.inventoryByLocation, locationId, currentQuantity + quantity);
        }
    }

    private handleStockPicked(event: CanonicalEvent, state: InterpretedState): void {
        const payload = event.payload as { locationId?: string; quantity?: number };
        const { locationId, quantity } = payload;
        if (locationId && quantity !== undefined) {
            const currentQuantity = safeGet(state.inventoryByLocation, locationId, 0);
            safeSet(state.inventoryByLocation, locationId, Math.max(0, currentQuantity - quantity));
        }
    }

    private handleStockRelocated(event: CanonicalEvent, state: InterpretedState): void {
        const payload = event.payload as { fromLocationId?: string; toLocationId?: string; quantity?: number };
        const { fromLocationId, toLocationId, quantity } = payload;
        
        if (fromLocationId && quantity !== undefined) {
            const currentFromQuantity = safeGet(state.inventoryByLocation, fromLocationId, 0);
            safeSet(state.inventoryByLocation, fromLocationId, Math.max(0, currentFromQuantity - quantity));
        }
        
        if (toLocationId && quantity !== undefined) {
            const currentToQuantity = safeGet(state.inventoryByLocation, toLocationId, 0);
            safeSet(state.inventoryByLocation, toLocationId, currentToQuantity + quantity);
        }
    }

    private handleIncidentReported(event: CanonicalEvent, state: InterpretedState): void {
        const payload = event.payload as { incidentId?: string; locationId?: string };
        const { incidentId, locationId } = payload;
        if (incidentId) {
            state.openIncidents.add(incidentId);
            if (locationId) {
                const existing = safeGet<Set<string> | undefined>(
                    state.openIncidentsByLocation, locationId, undefined
                );
                if (!existing) {
                    safeSet(state.openIncidentsByLocation, locationId, new Set<string>());
                }
                safeGet(state.openIncidentsByLocation, locationId, new Set<string>()).add(incidentId);
            }
        }
    }

    private handleIncidentStatusUpdated(event: CanonicalEvent, state: InterpretedState): void {
        const payload = event.payload as { incidentId?: string; newStatus?: string; locationId?: string };
        const { incidentId, newStatus, locationId } = payload;
        if (incidentId && newStatus) {
            const isIncidentOpen = ['open', 'OPEN'].includes(newStatus);
            if (isIncidentOpen) {
                state.openIncidents.add(incidentId);
                // Status updates might not carry locationId if the domain doesn't pass it,
                // but if they do, ensure we track it.
                if (locationId) {
                    const existing = safeGet<Set<string> | undefined>(
                        state.openIncidentsByLocation, locationId, undefined
                    );
                    if (!existing) {
                        safeSet(state.openIncidentsByLocation, locationId, new Set<string>());
                    }
                    safeGet(state.openIncidentsByLocation, locationId, new Set<string>()).add(incidentId);
                }
            } else {
                state.openIncidents.delete(incidentId);
                for (const trackedLocationId of Object.keys(state.openIncidentsByLocation)) {
                    safeGet(state.openIncidentsByLocation, trackedLocationId, new Set<string>()).delete(incidentId);
                }
            }
        }
    }

    private handleMovementCreated(event: CanonicalEvent, state: InterpretedState): void {
        state.totalMovementsProcessed += 1;
    }
}
