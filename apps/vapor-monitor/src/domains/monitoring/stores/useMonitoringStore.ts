import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import echo from '@/services/echo'

interface LocationDto {
    id: string;
    zone: string | null;
}

interface InventoryDto {
    locationId: string;
    quantityOnHand: number;
}

interface IncidentDto {
    id: string;
    type: string;
    description: string;
    status: string;
    createdAt: string;
}

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
    const isLoading = ref<boolean>(true);
    const error = ref<string | null>(null);

    const totalInventoryCount = ref<number>(0);
    const openIncidentsCount = ref<number>(0);
    const inventoryDifferences = ref<number>(0); 
    
    const locationZoneMap = ref<Record<string, string>>({});
    const locationQtyMap = ref<Record<string, number>>({});
    const totalLocationsPerZone = ref<Record<string, number>>({});
    
    const recentIncidents = ref<IncidentFeedItem[]>([]);
    const recentInbound = ref<MovementFeedItem[]>([]);
    const recentOutbound = ref<MovementFeedItem[]>([]);

    const zoneOccupancy = computed<ZoneOccupancyItem[]>(() => {
        const occupiedPerZone: Record<string, number> = {};
        
        for (const [locationId, quantity] of Object.entries(locationQtyMap.value)) {
            if (quantity > 0) {
                const zone = locationZoneMap.value[locationId];
                if (zone) {
                    occupiedPerZone[zone] = (occupiedPerZone[zone] || 0) + 1;
                }
            }
        }
        
        return Object.keys(totalLocationsPerZone.value).map(zone => {
            const total = totalLocationsPerZone.value[zone];
            const occupied = occupiedPerZone[zone] || 0;
            return {
                zone,
                occupied,
                total,
                percentage: total > 0 ? Math.round((occupied / total) * 100) : 0
            };
        }).sort((a, b) => a.zone.localeCompare(b.zone));
    });

    const activeLocationsCount = computed<number>(() => {
        return Object.values(locationQtyMap.value).filter(quantity => quantity > 0).length;
    });

    async function fetchInitialData(): Promise<void> {
        isLoading.value = true;
        error.value = null;

        try {
            await Promise.all([
                loadLocations(),
                loadInventory(),
                loadIncidents(),
                loadMovements()
            ]);
            
            listenForEvents();
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

    async function loadLocations(): Promise<void> {
        const response = await fetch('/api/locations');
        if (!response.ok) {
            throw new Error(`Location service unavailable (${response.status}).`);
        }
        const { data: warehouseLocations } = await response.json() as { data: LocationDto[] };
        
        const zonesConfigured: Record<string, number> = {};
        const locationZoneMapping: Record<string, string> = {};
        
        warehouseLocations.forEach((location: LocationDto) => {
            const zone = location.zone || 'Unassigned';
            locationZoneMapping[location.id] = zone;
            zonesConfigured[zone] = (zonesConfigured[zone] || 0) + 1;
        });
        
        locationZoneMap.value = locationZoneMapping;
        totalLocationsPerZone.value = zonesConfigured;
    }

    async function loadInventory(): Promise<void> {
        const response = await fetch('/api/inventory');
        if (!response.ok) {
            throw new Error(`Inventory service unavailable (${response.status}).`);
        }
        const { data: stockItems } = await response.json() as { data: InventoryDto[] };
        
        const locationQuantityMapping: Record<string, number> = {};
        let aggregateStockCount = 0;
        
        stockItems.forEach((stockItem: InventoryDto) => {
            aggregateStockCount += stockItem.quantityOnHand;
            locationQuantityMapping[stockItem.locationId] = (locationQuantityMapping[stockItem.locationId] || 0) + stockItem.quantityOnHand;
        });
        
        locationQtyMap.value = locationQuantityMapping;
        totalInventoryCount.value = aggregateStockCount;
    }

    async function loadIncidents(): Promise<void> {
        const response = await fetch('/api/incidents');
        if (!response.ok) {
            throw new Error(`Incident service unavailable (${response.status}).`);
        }
        const { data: reportedIncidents } = await response.json() as { data: IncidentDto[] };
        
        recentIncidents.value = reportedIncidents.map((incident: IncidentDto) => ({
            id: incident.id,
            type: incident.type,
            description: incident.description,
            time: incident.createdAt
        }));
        
        openIncidentsCount.value = reportedIncidents.filter((incident: IncidentDto) => 
            incident.status === 'open' || incident.status === 'OPEN'
        ).length;
    }

    async function loadMovements(): Promise<void> {
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

        inventoryDifferences.value = warehouseMovements
            .filter((movement: MovementDto) => movement.type === 'adjustment' || movement.type === 'ADJUSTMENT')
            .reduce((sum: number, movement: MovementDto) => sum + Math.abs(movement.quantity), 0);
    }

    function listenForEvents(): void {
        echo.channel('warehouse.monitoring')
            .listen('.inventory.stock.adjusted', handleInventoryStockAdjusted)
            .listen('.inventory.stock.received', handleInventoryStockReceived)
            .listen('.inventory.stock.picked', handleInventoryStockPicked)
            .listen('.inventory.stock.relocated', handleInventoryStockRelocated)
            .listen('.incident.reported', handleIncidentReported)
            .listen('.incident.status.updated', handleIncidentStatusUpdated)
            .listen('.movement.created', handleMovementCreated);
    }

    function handleInventoryStockAdjusted(event: BaseEvent<StockAdjustedPayload>): void {
        const previous = event.payload.previousQuantity || 0;
        const current = event.payload.newQuantity || 0;
        const delta = event.payload.deltaQuantity || (current - previous);
        
        inventoryDifferences.value += Math.abs(delta);
        totalInventoryCount.value += delta;
        
        if (event.payload.locationId) {
            locationQtyMap.value[event.payload.locationId] = Math.max(0, (locationQtyMap.value[event.payload.locationId] || 0) + delta);
        }
    }

    function handleInventoryStockReceived(event: BaseEvent<StockReceivedPayload>): void {
        totalInventoryCount.value += event.payload.quantity;
        if (event.payload.locationId) {
            locationQtyMap.value[event.payload.locationId] = (locationQtyMap.value[event.payload.locationId] || 0) + event.payload.quantity;
        }
    }

    function handleInventoryStockPicked(event: BaseEvent<StockPickedPayload>): void {
        totalInventoryCount.value -= event.payload.quantity;
        if (event.payload.locationId) {
            locationQtyMap.value[event.payload.locationId] = Math.max(0, (locationQtyMap.value[event.payload.locationId] || 0) - event.payload.quantity);
        }
    }

    function handleInventoryStockRelocated(event: BaseEvent<StockRelocatedPayload>): void {
        if (event.payload.sourceLocationId) {
            locationQtyMap.value[event.payload.sourceLocationId] = Math.max(0, (locationQtyMap.value[event.payload.sourceLocationId] || 0) - event.payload.quantity);
        }
        if (event.payload.destinationLocationId) {
            locationQtyMap.value[event.payload.destinationLocationId] = (locationQtyMap.value[event.payload.destinationLocationId] || 0) + event.payload.quantity;
        }
    }

    function handleIncidentReported(event: BaseEvent<IncidentReportedPayload>): void {
        openIncidentsCount.value += 1;
        if (!recentIncidents.value.some(incident => incident.id === event.payload.incidentId)) {
            recentIncidents.value.unshift({
                id: event.payload.incidentId,
                type: event.payload.type,
                description: event.payload.description,
                time: event.occurredAt
            });
            if (recentIncidents.value.length > 50) recentIncidents.value.pop();
        }
    }

    function handleIncidentStatusUpdated(event: BaseEvent<IncidentStatusUpdatedPayload>): void {
        const wasOpen = ['open', 'OPEN'].includes(event.payload.previousStatus);
        const isOpen = ['open', 'OPEN'].includes(event.payload.newStatus);
        
        if (wasOpen && !isOpen) {
            openIncidentsCount.value = Math.max(0, openIncidentsCount.value - 1);
        } else if (!wasOpen && isOpen) {
            openIncidentsCount.value += 1;
        }
    }

    function handleMovementCreated(event: BaseEvent<MovementCreatedPayload>): void {
        const movementEntry: MovementFeedItem = {
            id: event.payload.movementId,
            type: event.payload.type,
            quantity: event.payload.quantity,
            time: event.occurredAt
        };
        
        if (['receipt', 'putaway', 'return_internal', 'RECEIPT', 'PUTAWAY'].includes(event.payload.type)) {
            if (!recentInbound.value.some(movement => movement.id === movementEntry.id)) {
                recentInbound.value.unshift(movementEntry);
                if (recentInbound.value.length > 50) recentInbound.value.pop();
            }
        } else if (['picking', 'relocation', 'PICKING', 'RELOCATION'].includes(event.payload.type)) {
            if (!recentOutbound.value.some(movement => movement.id === movementEntry.id)) {
                recentOutbound.value.unshift(movementEntry);
                if (recentOutbound.value.length > 50) recentOutbound.value.pop();
            }
        }
    }

    return {
        isLoading,
        error,
        totalInventoryCount,
        activeLocationsCount,
        openIncidentsCount,
        inventoryDifferences,
        zoneOccupancy,
        recentIncidents,
        recentInbound,
        recentOutbound,
        fetchInitialData
    }
})
