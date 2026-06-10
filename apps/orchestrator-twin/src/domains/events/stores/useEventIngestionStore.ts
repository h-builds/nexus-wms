import { defineStore } from 'pinia'
import { ref } from 'vue'
import echo from '@/domains/shared/services/echo';

export interface CanonicalEvent {
    eventId: string;
    eventType: string;
    eventVersion: number;
    occurredAt: string;
    actorId: string;
    correlationId: string;
    causationId: string;
    payload: unknown;
}

export const useEventIngestionStore = defineStore('eventIngestion', () => {
    const rawEvents = ref<CanonicalEvent[]>([]);
    const isListening = ref<boolean>(false);

    function startListening(): void {
        if (isListening.value) return;

        console.log('[EventIngestion] Starting WebSocket listeners on warehouse.monitoring');

        echo.channel('warehouse.monitoring')
            .listen('.inventory.stock.adjusted', handleCanonicalEvent)
            .listen('.inventory.stock.received', handleCanonicalEvent)
            .listen('.inventory.stock.picked', handleCanonicalEvent)
            .listen('.inventory.stock.relocated', handleCanonicalEvent)
            .listen('.incident.reported', handleCanonicalEvent)
            .listen('.incident.status.updated', handleCanonicalEvent)
            .listen('.movement.created', handleCanonicalEvent)
            .listen('.product.created', handleCanonicalEvent)
            .listen('.location.created', handleCanonicalEvent)
            .listen('.location.status.updated', handleCanonicalEvent);

        isListening.value = true;
    }

    function handleCanonicalEvent(event: CanonicalEvent): void {
        console.log(`[EventIngestion] Received canonical event: ${event.eventType}`, event);
        rawEvents.value.unshift(event);
        
        if (rawEvents.value.length > 100) {
            rawEvents.value.pop();
        }
    }

    function clearLog(): void {
        rawEvents.value = [];
    }

    return {
        rawEvents,
        isListening,
        startListening,
        clearLog
    }
})
