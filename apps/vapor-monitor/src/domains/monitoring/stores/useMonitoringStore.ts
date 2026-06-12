import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useEventStateStore, type InventorySnapshotEntry, type IncidentSnapshotEntry } from '@/domains/events/stores/useEventStateStore'
import { useEventIngestionStore } from '@/domains/events/stores/useEventIngestionStore'
import { safeGet, safeSet } from '../../shared/safeRecord'


interface LocationDto {
    id: string;
    zone: string | null;
}

type InventoryDto = InventorySnapshotEntry;

type IncidentDto = IncidentSnapshotEntry;

interface MovementDto {
    id: string;
    type: string;
    quantity: number;
    performedAt?: string;
    createdAt: string;
}

export interface IncidentFeedItem {
    id: string;
    type: string;
    description: string;
    time: string;
}

export interface MovementFeedItem {
    id: string;
    type: string;
    quantity: number;
    time: string;
}

export interface ZoneOccupancyItem {
    zone: string;
    occupied: number;
    total: number;
    percentage: number;
}

interface BaseEvent<T> {
    occurredAt: string;
    payload: T;
}

interface StockAdjustedPayload {
    locationId?: string;
    deltaQuantity?: number;
    newQuantity?: number;
    previousQuantity?: number;
}

interface StockReceivedPayload {
    locationId?: string;
    quantity: number;
}

interface StockPickedPayload {
    locationId?: string;
    quantity: number;
}

interface StockRelocatedPayload {
    sourceLocationId?: string;
    destinationLocationId?: string;
    quantity: number;
}

interface IncidentReportedPayload {
    incidentId: string;
    type: string;
    description: string;
}

interface IncidentStatusUpdatedPayload {
    previousStatus: string;
    newStatus: string;
}

interface MovementCreatedPayload {
    movementId: string;
    type: string;
    quantity: number;
}

export const useMonitoringStore = defineStore('monitoring', () => {
    const stateStore = useEventStateStore();
    const ingestionStore = useEventIngestionStore();
    
    const isLoading = ref<boolean>(true);
    const error = ref<string | null>(null);

    const locationZoneMap = ref<Record<string, string>>({});
    const totalLocationsPerZone = ref<Record<string, number>>({});
    
    const recentIncidents = ref<IncidentFeedItem[]>([]);
    const recentInbound = ref<MovementFeedItem[]>([]);
    const recentOutbound = ref<MovementFeedItem[]>([]);

    const totalMovementsProcessed = computed(() => stateStore.totalMovementsProcessed);
    const openIncidentsCount = computed(() => stateStore.openIncidentsCount);
    
    const totalInventoryCount = computed(() => {
        let sum = 0;
        for (const quantity of Object.values(stateStore.inventoryByLocation)) {
            sum += quantity;
        }
        return sum;
    });

    const zoneOccupancy = computed<ZoneOccupancyItem[]>(() => {
        const occupiedPerZone: Record<string, number> = {};
        
        for (const [locationId, quantity] of Object.entries(stateStore.inventoryByLocation)) {
            if (quantity > 0) {
                const zone = safeGet(locationZoneMap.value, locationId, undefined as string | undefined);
                if (zone) {
                    safeSet(occupiedPerZone, zone, safeGet(occupiedPerZone, zone, 0) + 1);
                }
            }
        }
        
        return Object.keys(totalLocationsPerZone.value).map(zone => {
            const total = safeGet(totalLocationsPerZone.value, zone, 0);
            const occupied = safeGet(occupiedPerZone, zone, 0);
            return {
                zone,
                occupied,
                total,
                percentage: total > 0 ? Math.round((occupied / total) * 100) : 0
            };
        }).sort((a, b) => a.zone.localeCompare(b.zone));
    });

    const activeLocationsCount = computed<number>(() => {
        return Object.values(stateStore.inventoryByLocation).filter(quantity => quantity > 0).length;
    });

    async function fetchInitialData(): Promise<void> {
        isLoading.value = true;
        error.value = null;

        try {
            const [
                locationsData,
                inventoryData,
                incidentsData,
                movementsData
            ] = await Promise.all([
                loadLocations(),
                loadInventory(),
                loadIncidents(),
                loadMovements()
            ]);
            
            stateStore.initializeFromBaseline(inventoryData, incidentsData);
        } catch (fetchError: unknown) {
            console.error(fetchError);
            if (fetchError instanceof Error) {
                error.value = fetchError.message;
            } else {
                error.value = "Failed to synchronize operational context.";
            }
        } finally {
            isLoading.value = false;
        }
    }

    async function loadLocations(): Promise<LocationDto[]> {
        const response = await fetch('/api/locations');
        if (!response.ok) {
            throw new Error(`Location service unavailable (${response.status}).`);
        }
        const { data: warehouseLocations } = await response.json() as { data: LocationDto[] };
        
        const zonesConfigured: Record<string, number> = {};
        const locationZoneMapping: Record<string, string> = {};
        
        warehouseLocations.forEach((location: LocationDto) => {
            const zone = location.zone || 'Unassigned';
            const key = location.id;
            if (key === '__proto__' || key === 'constructor' || key === 'prototype') return;
            safeSet(locationZoneMapping, key, zone);
            safeSet(zonesConfigured, zone, safeGet(zonesConfigured, zone, 0) + 1);
        });
        
        locationZoneMap.value = locationZoneMapping;
        totalLocationsPerZone.value = zonesConfigured;
        return warehouseLocations;
    }

    async function loadInventory(): Promise<InventoryDto[]> {
        const response = await fetch('/api/inventory');
        if (!response.ok) {
            throw new Error(`Inventory service unavailable (${response.status}).`);
        }
        const { data: stockItems } = await response.json() as { data: InventoryDto[] };
        
        return stockItems;
    }

    async function loadIncidents(): Promise<IncidentDto[]> {
        const response = await fetch('/api/incidents');
        if (!response.ok) {
            throw new Error(`Incident service unavailable (${response.status}).`);
        }
        const { data: reportedIncidents } = await response.json() as { data: IncidentDto[] };
        
        recentIncidents.value = reportedIncidents.map((incident: IncidentDto) => ({
            id: incident.id,
            type: incident.type,
            description: incident.description,
            time: incident.createdAt ?? ''
        }));
        
        return reportedIncidents;
    }

    async function loadMovements(): Promise<MovementDto[]> {
        const response = await fetch('/api/movements');
        if (!response.ok) {
            throw new Error(`Movement service unavailable (${response.status}).`);
        }
        const { data: warehouseMovements } = await response.json() as { data: MovementDto[] };
        
        recentInbound.value = warehouseMovements
            .filter((movement: MovementDto) => ['receipt', 'putaway', 'return_internal', 'RECEIPT', 'PUTAWAY'].includes(movement.type))
            .map((movement: MovementDto) => ({ 
                id: movement.id, 
                type: movement.type, 
                quantity: movement.quantity, 
                time: movement.performedAt || movement.createdAt 
            }));

        recentOutbound.value = warehouseMovements
            .filter((movement: MovementDto) => ['picking', 'relocation', 'PICKING', 'RELOCATION'].includes(movement.type))
            .map((movement: MovementDto) => ({ 
                id: movement.id, 
                type: movement.type, 
                quantity: movement.quantity, 
                time: movement.performedAt || movement.createdAt 
            }));
            
        return warehouseMovements;
    }

    watch(
        () => ingestionStore.rawEvents,
        (events) => {
            if (events.length === 0) return;
            const latestEvent = events[0];
            
            if (latestEvent.eventType === 'incident.reported') {
                const payload = latestEvent.payload as IncidentReportedPayload;
                recentIncidents.value.unshift({
                    id: payload.incidentId,
                    type: payload.type,
                    description: payload.description,
                    time: latestEvent.occurredAt
                });
                if (recentIncidents.value.length > 50) recentIncidents.value.pop();
            } else if (latestEvent.eventType === 'movement.created') {
                const payload = latestEvent.payload as MovementCreatedPayload;
                const isOutbound = ['picking', 'relocation', 'PICKING', 'RELOCATION'].includes(payload.type);
                const isInbound = ['receipt', 'putaway', 'return_internal', 'RECEIPT', 'PUTAWAY'].includes(payload.type);
                
                const feedItem: MovementFeedItem = {
                    id: payload.movementId,
                    type: payload.type,
                    quantity: payload.quantity,
                    time: latestEvent.occurredAt
                };

                if (isInbound) {
                    recentInbound.value.unshift(feedItem);
                    if (recentInbound.value.length > 50) recentInbound.value.pop();
                } else if (isOutbound) {
                    recentOutbound.value.unshift(feedItem);
                    if (recentOutbound.value.length > 50) recentOutbound.value.pop();
                }
            }
        },
        { deep: true }
    );

    return {
        isLoading,
        error,
        totalInventoryCount,
        activeLocationsCount,
        openIncidentsCount,
        totalMovementsProcessed,
        zoneOccupancy,
        recentIncidents,
        recentInbound,
        recentOutbound,
        fetchInitialData
    }
})
