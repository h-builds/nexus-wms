import { defineStore } from 'pinia';
import { ref, watch, computed } from 'vue';
import { useEventIngestionStore } from './useEventIngestionStore';
import { EventInterpreter, type InterpretedState } from '../services/EventInterpreter';
import type { CanonicalEvent } from './useEventIngestionStore';

export interface InventorySnapshotEntry {
    locationId: string;
    quantityOnHand: number;
}

export interface IncidentSnapshotEntry {
    id: string;
    locationId: string;
    type: string;
    description: string;
    status: string;
    createdAt?: string;
}

export const useEventStateStore = defineStore('eventState', () => {
    const ingestionStore = useEventIngestionStore();
    const interpreter = new EventInterpreter();

    const state = ref<InterpretedState>({
        inventoryByLocation: {},
        openIncidents: new Set<string>(),
        openIncidentsByLocation: {},
        totalMovementsProcessed: 0,
        processedEventIds: new Set<string>(),
        lastProcessedEventTime: null,
        driftedEvents: [],
    });

    const isInitialized = ref(false);

    function buildInventoryBaselineEvent(entry: InventorySnapshotEntry, index: number): CanonicalEvent {
        return {
            eventId: `baseline-inv-${index}`,
            eventType: '.inventory.stock.received',
            eventVersion: 1,
            occurredAt: new Date().toISOString(),
            actorId: 'system',
            correlationId: 'baseline-init',
            causationId: 'baseline-init',
            payload: {
                locationId: entry.locationId,
                quantity: entry.quantityOnHand,
            },
        };
    }

    function buildIncidentBaselineEvent(entry: IncidentSnapshotEntry, index: number): CanonicalEvent {
        return {
            eventId: `baseline-inc-${index}`,
            eventType: '.incident.reported',
            eventVersion: 1,
            occurredAt: entry.createdAt ?? new Date().toISOString(),
            actorId: 'system',
            correlationId: 'baseline-init',
            causationId: 'baseline-init',
            payload: {
                incidentId: entry.id,
                locationId: entry.locationId,
                type: entry.type,
                description: entry.description,
            },
        };
    }

    /** Hydrates deterministic state from HTTP snapshots by replaying synthetic canonical events through the interpreter. */
    function initializeFromBaseline(inventorySnapshot: InventorySnapshotEntry[], incidentsSnapshot: IncidentSnapshotEntry[]): void {
        if (isInitialized.value) return;

        inventorySnapshot.forEach((entry, index) => {
            interpreter.interpret(buildInventoryBaselineEvent(entry, index), state.value);
        });

        const openStatuses = new Set(['open', 'OPEN']);
        incidentsSnapshot
            .filter((entry) => openStatuses.has(entry.status))
            .forEach((entry, index) => {
                interpreter.interpret(buildIncidentBaselineEvent(entry, index), state.value);
            });

        state.value.lastProcessedEventTime = new Date().toISOString();
        isInitialized.value = true;
    }

    /**
     * Events arrive via unshift (newest at index 0). We collect unseen events
     * then replay them oldest-first so the interpreter accumulates state in causal order.
     */
    watch(
        () => ingestionStore.rawEvents,
        (events) => {
            if (events.length === 0) return;

            try {
                const unprocessedEvents: CanonicalEvent[] = [];
                for (const canonicalEvent of events) {
                    if (state.value.processedEventIds.has(canonicalEvent.eventId)) break;
                    unprocessedEvents.push(canonicalEvent);
                }

                for (let i = unprocessedEvents.length - 1; i >= 0; i--) {
                    interpreter.interpret(unprocessedEvents[i], state.value);
                }
            } catch (interpretationError: unknown) {
                const message = interpretationError instanceof Error
                    ? interpretationError.message
                    : 'Unknown interpretation failure';
                console.error(`[EventStateStore] Failed to interpret incoming events: ${message}`);
            }
        },
        { deep: true }
    );

    const inventoryByLocation = computed(() => state.value.inventoryByLocation);
    const openIncidentsCount = computed(() => state.value.openIncidents.size);
    const totalMovementsProcessed = computed(() => state.value.totalMovementsProcessed);

    /** Projected snapshot for the Debugger Validation Panel — avoids exposing mutable internals. */
    const debuggerState = computed(() => ({
        timestamp: state.value.lastProcessedEventTime,
        inventoryLocationCount: Object.keys(state.value.inventoryByLocation).length,
        openIncidentsCount: state.value.openIncidents.size,
        totalMovementsProcessed: state.value.totalMovementsProcessed,
        lastProcessedEventId: state.value.processedEventIds.size > 0
            ? Array.from(state.value.processedEventIds).pop() ?? null
            : null,
    }));

    return {
        isInitialized,
        initializeFromBaseline,
        inventoryByLocation,
        openIncidentsCount,
        totalMovementsProcessed,
        debuggerState,
        _rawStateRef: state,
    };
});
