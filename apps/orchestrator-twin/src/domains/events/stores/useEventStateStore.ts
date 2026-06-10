import { defineStore } from 'pinia';
import { ref, watch, computed } from 'vue';
import { useEventIngestionStore } from './useEventIngestionStore';
import { EventInterpreter, type InterpretedState } from '../services/EventInterpreter';
import type { ApiStockItem } from '../../occupancy/types';
import type { ApiIncident } from '../../incidents/types';

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

    /**
     * Initializes the state store from raw HTTP snapshots.
     * We map these into synthetic canonical events to rigorously reuse EventInterpreter logic.
     */
    function initializeFromBaseline(inventorySnapshot: ApiStockItem[], incidentsSnapshot: ApiIncident[]) {
        if (isInitialized.value) return;

        inventorySnapshot.forEach((inv, index) => {
            interpreter.interpret({
                eventId: `baseline-inv-${index}`,
                eventType: '.inventory.stock.received',
                eventVersion: 1,
                occurredAt: new Date().toISOString(),
                actorId: 'system',
                correlationId: 'baseline-init',
                causationId: 'baseline-init',
                payload: {
                    locationId: inv.locationId,
                    quantity: inv.quantityOnHand
                }
            }, state.value);
        });

        incidentsSnapshot.forEach((inc, index) => {
            if (['open', 'OPEN'].includes(inc.status)) {
                interpreter.interpret({
                    eventId: `baseline-inc-${index}`,
                    eventType: '.incident.reported',
                    eventVersion: 1,
                    occurredAt: inc.createdAt || new Date().toISOString(),
                    actorId: 'system',
                    correlationId: 'baseline-init',
                    causationId: 'baseline-init',
                    payload: {
                        incidentId: inc.id,
                        locationId: inc.locationId,
                        type: inc.type,
                        description: inc.description
                    }
                }, state.value);
            }
        });

        state.value.lastProcessedEventTime = new Date().toISOString();
        isInitialized.value = true;
    }

    // We track the number of items we have processed from the rolling ingestion log.
    // Because ingestionStore.rawEvents uses unshift(), the newest events arrive at index 0.
    // But since it's a fixed-length window, we just track the most recently added event ID.
    watch(
        () => ingestionStore.rawEvents,
        (events) => {
            if (events.length === 0) return;
            
            // Because new events are unshifted, we just process from the start of the array
            // until we hit an event we've already processed.
            // But they arrive chronologically fast, so we traverse the incoming slice from oldest to newest to maintain math.
            const unprocessed = [];
            for (const event of events) {
                if (state.value.processedEventIds.has(event.eventId)) {
                    break;
                }
                unprocessed.push(event);
            }

            unprocessed.reverse();
            for (const event of unprocessed) {
                interpreter.interpret(event, state.value);
            }
        },
        { deep: true }
    );

    // Provide readonly proxies to prevent domain consumers from directly mutating interpreted state
    const inventoryByLocation = computed(() => state.value.inventoryByLocation);
    const openIncidentsCount = computed(() => state.value.openIncidents.size);
    const totalMovementsProcessed = computed(() => state.value.totalMovementsProcessed);
    
    const debuggerState = computed(() => ({
        timestamp: state.value.lastProcessedEventTime,
        inventoryKeys: Object.keys(state.value.inventoryByLocation).length,
        openIncidentsCount: state.value.openIncidents.size,
        totalMovementsProcessed: state.value.totalMovementsProcessed,
        lastProcessedEventId: state.value.processedEventIds.size > 0 
           ? Array.from(state.value.processedEventIds).pop() 
           : null
    }));

    return {
        isInitialized,
        initializeFromBaseline,
        inventoryByLocation,
        openIncidentsCount,
        totalMovementsProcessed,
        debuggerState,
        _rawStateRef: state // Exported strictly for deep JSON inspection
    };
});
