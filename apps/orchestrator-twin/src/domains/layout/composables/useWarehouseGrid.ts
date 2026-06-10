import { ref, computed } from 'vue';
import type { LayoutSnapshot } from '../types';
import type { BinOverlay, BinVisualState } from '../../shared/binState';
import type { HeatmapIntensity, ZoneHeatmapEntry } from '../../heatmap/types';
import type { Recommendation } from '../../recommendations/types';
import type { SimulationResult } from '../../simulation/types';
import type { ApiStockItem } from '../../occupancy/types';
import type { ApiIncident } from '../../incidents/types';
import { resolveBinState } from '../../shared/binState';
import { LayoutService } from '../service';
import { useEventStateStore } from '../../events/stores/useEventStateStore';
import { safeGet, safeSet } from '../../shared/safeRecord';
import { fetchFromApi } from '../../shared/api';

export function useWarehouseGrid() {
  const stateStore = useEventStateStore();
  const layoutSnapshot = ref<LayoutSnapshot | null>(null);
  const heatmapEntries = ref<ZoneHeatmapEntry[]>([]);
  const recommendations = ref<Recommendation[]>([]);
  const isLayoutLoading = ref(true);
  const layoutLoadError = ref<string | null>(null);

  const heatmapEnabled = ref(true);
  const showOccupancy = ref(true);
  const showIncidents = ref(true);

  const rawOverlays = computed<Record<string, BinOverlay>>(() => {
    const computedOverlays: Record<string, BinOverlay> = {};
    if (!layoutSnapshot.value) return computedOverlays;

    const zoneDensityMap = new Map<string, number>();
    for (const warehouse of layoutSnapshot.value.warehouses) {
      for (const zone of warehouse.zones) {
        let occupiedCount = 0;
        let totalCount = 0;
        for (const aisle of zone.aisles) {
          for (const rack of aisle.racks) {
             for (const bin of rack.bins) {
                totalCount++;
                if (safeGet(stateStore.inventoryByLocation, bin.locationId, 0) > 0) {
                   occupiedCount++;
                }
             }
          }
        }
        zoneDensityMap.set(zone.id, totalCount > 0 ? (occupiedCount / totalCount) * 100 : 0);
      }
    }

    for (const warehouse of layoutSnapshot.value.warehouses) {
      for (const zone of warehouse.zones) {
        const zonePct = zoneDensityMap.get(zone.id) ?? 0;

        for (const aisle of zone.aisles) {
          for (const rack of aisle.racks) {
            for (const bin of rack.bins) {
              const locId = bin.locationId;
              const isBlocked = bin.isBlocked;
              const qty = safeGet(stateStore.inventoryByLocation, locId, 0);
              const isOccupied = qty > 0;
              
              const incidentMapped = safeGet(stateStore._rawStateRef.openIncidentsByLocation, locId, undefined);
              const incidentCount = incidentMapped ? incidentMapped.size : 0;
              
              const highestSeverity = incidentCount > 0 ? 'high' : null;

              computedOverlays[locId] = {
                state: resolveBinState(isBlocked, incidentCount, isOccupied),
                incidentCount,
                highestSeverity,
                densityPct: zonePct,
              };
            }
          }
        }
      }
    }
    return computedOverlays;
  });

  const filteredOverlays = computed<Record<string, BinOverlay>>(() => {
    if (showOccupancy.value && showIncidents.value) return rawOverlays.value;

    const filteredMap: Record<string, BinOverlay> = {};
    for (const [locId, overlay] of Object.entries(rawOverlays.value)) {
      let state: BinVisualState = overlay.state;
      let incidentCount = overlay.incidentCount;
      let highestSeverity = overlay.highestSeverity;

      if (!showIncidents.value && state === 'incident') {
        state = (showOccupancy.value && safeGet(stateStore.inventoryByLocation, locId, 0) > 0)
          ? 'occupied'
          : 'empty';
        incidentCount = 0;
        highestSeverity = null;
      }

      if (!showOccupancy.value && state === 'occupied') {
        state = 'empty';
      }

      safeSet(filteredMap, locId, { state, incidentCount, highestSeverity, densityPct: overlay.densityPct });
    }
    return filteredMap;
  });

  const heatmapByZone = computed<Record<string, HeatmapIntensity>>(() => {
    const intensityMap: Record<string, HeatmapIntensity> = {};
    for (const entry of heatmapEntries.value) {
      safeSet(intensityMap, entry.zoneId, entry.intensity);
    }
    return intensityMap;
  });

  function getZoneIntensity(zoneId: string): HeatmapIntensity | null {
    return safeGet(heatmapByZone.value, zoneId, null as HeatmapIntensity | null);
  }

  function runSimulation(units: number): SimulationResult | null {
    return null;
  }

  function clearSimulationRecommendations(): void {
    recommendations.value = [];
  }

  async function fetchDomainBaseline<T>(endpoint: string): Promise<T[]> {
    const fetchedItems: T[] = [];
    let currentPage = 1;
    let hasMorePages = true;
    while(hasMorePages) {
      const response = await fetchFromApi<T[]>(endpoint, { page: currentPage, per_page: 100 });
      fetchedItems.push(...response.data);
      if (response.meta.currentPage >= response.meta.totalPages) {
        hasMorePages = false;
      } else {
        currentPage++;
      }
    }
    return fetchedItems;
  }

  async function loadWarehouseData() {
    try {
      const layout = await new LayoutService().getLayoutSnapshot();
      layoutSnapshot.value = layout;

      const [inventoryRaw, incidentsRaw] = await Promise.all([
        fetchDomainBaseline<ApiStockItem>('/inventory'),
        fetchDomainBaseline<ApiIncident>('/incidents')
      ]);

      stateStore.initializeFromBaseline(inventoryRaw, incidentsRaw);
      
      heatmapEntries.value = [];
      recommendations.value = [];
    } catch (error) {
      layoutLoadError.value = error instanceof Error ? error.message : 'A domain error occurred while loading warehouse data';
    } finally {
      isLayoutLoading.value = false;
    }
  }

  return {
    isLayoutLoading,
    layoutLoadError,
    layoutSnapshot,
    recommendations,
    heatmapEnabled,
    showOccupancy,
    showIncidents,
    filteredOverlays,
    getZoneIntensity,
    runSimulation,
    clearSimulationRecommendations,
    loadWarehouseData
  };
}
