<template>
  <div class="warehouse-grid">
    <template v-if="loading">
      <div class="grid-status">
        <span class="status-icon">&#x23F3;</span>
        <span>Loading warehouse data&hellip;</span>
      </div>
    </template>

    <template v-else-if="error">
      <div class="grid-status grid-error">
        <span class="status-icon">&#x26A0;&#xFE0F;</span>
        <span>{{ error }}</span>
      </div>
    </template>

    <template v-else-if="layoutSnapshot && layoutSnapshot.warehouses.length > 0">
      <!-- Toolbar: legend + toggles -->
      <div class="toolbar">
        <div class="legend-group">
          <span class="legend-swatch"><span class="swatch swatch-empty"></span> Empty</span>
          <span class="legend-swatch"><span class="swatch swatch-occupied"></span> Occupied</span>
          <span class="legend-swatch"><span class="swatch swatch-incident"></span> Incident</span>
          <span class="legend-swatch"><span class="swatch swatch-blocked"></span> Blocked</span>
        </div>
        <div class="toggle-group">
          <label class="toggle-label">
            <input type="checkbox" v-model="showOccupancy" />
            <span>Occupancy</span>
          </label>
          <label class="toggle-label">
            <input type="checkbox" v-model="showIncidents" />
            <span>Incidents</span>
          </label>
          <label class="toggle-label">
            <input type="checkbox" v-model="heatmapEnabled" />
            <span>Heatmap</span>
          </label>
        </div>
      </div>

      <!-- Warehouse sections -->
      <div
        v-for="warehouse in layoutSnapshot.warehouses"
        :key="warehouse.warehouseCode"
        class="warehouse-section"
      >
        <div class="warehouse-header">
          <h2 class="warehouse-title">{{ warehouse.warehouseCode }}</h2>
          <span class="warehouse-meta">{{ warehouse.zones.length }} zone(s)</span>
        </div>
        <div class="zones-grid">
          <ZoneView
            v-for="zone in warehouse.zones"
            :key="zone.id"
            :zone="zone"
            :overlays="filteredOverlays"
            :heatmap-intensity="heatmapEnabled ? getZoneIntensity(zone.id) : null"
          />
        </div>
      </div>
    </template>

    <template v-else>
      <div class="grid-status">
        <span class="status-icon">&#x1F4E6;</span>
        <span>No warehouse locations found.</span>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import type { LayoutSnapshot } from '../../domains/layout/types';
import type { OccupancySnapshot } from '../../domains/occupancy/types';
import type { IncidentsSnapshot } from '../../domains/incidents/types';
import type { BinOverlay, BinVisualState } from '../../domains/shared/binState';
import type { HeatmapIntensity, ZoneHeatmapEntry } from '../../domains/heatmap/types';
import type { Recommendation } from '../../domains/recommendations/types';
import type { SimulationResult } from '../../domains/simulation/types';
import { resolveBinState } from '../../domains/shared/binState';
import { LayoutService } from '../../domains/layout/service';
import { OccupancyService } from '../../domains/occupancy/service';
import { IncidentsService } from '../../domains/incidents/service';
import { HeatmapService } from '../../domains/heatmap/service';
import { RecommendationService } from '../../domains/recommendations/service';
import { SimulationService } from '../../domains/simulation/service';
import ZoneView from './ZoneView.vue';

// --- State ---
const layoutSnapshot = ref<LayoutSnapshot | null>(null);
const occupancySnapshot = ref<OccupancySnapshot | null>(null);
const incidentsSnapshot = ref<IncidentsSnapshot | null>(null);
const heatmapEntries = ref<ZoneHeatmapEntry[]>([]);
const recommendations = ref<Recommendation[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);

// --- Visibility toggles ---
const heatmapEnabled = ref(true);
const showOccupancy = ref(true);
const showIncidents = ref(true);

// --- Expose for parent ---
defineExpose({
  recommendations,
  layoutSnapshot,
  occupancySnapshot,
  runSimulation,
  clearSimulationRecommendations,
});

// --- Raw overlay computation (all layers) ---
const rawOverlays = computed<Record<string, BinOverlay>>(() => {
  const computedOverlays: Record<string, BinOverlay> = {};
  if (!layoutSnapshot.value) return computedOverlays;

  // Build zone density lookup for height mapping
  const zoneDensityMap = new Map<string, number>();
  if (occupancySnapshot.value) {
    for (const zd of occupancySnapshot.value.zoneDensities) {
      zoneDensityMap.set(zd.zoneId, zd.densityPercentage);
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
            const isOccupied =
              occupancySnapshot.value?.locations[locId]?.isOccupied ?? false;
            const locIncidents = incidentsSnapshot.value?.locations[locId];
            const incidentCount = locIncidents?.openCount ?? 0;
            const highestSeverity = locIncidents?.highestSeverity ?? null;

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

// --- Filtered overlays based on toggle state ---
const filteredOverlays = computed<Record<string, BinOverlay>>(() => {
  if (showOccupancy.value && showIncidents.value) return rawOverlays.value;

  const filteredMap: Record<string, BinOverlay> = {};
  for (const [locId, overlay] of Object.entries(rawOverlays.value)) {
    let state: BinVisualState = overlay.state;
    let incidentCount = overlay.incidentCount;
    let highestSeverity = overlay.highestSeverity;

    // If incidents layer is off, suppress incident state
    if (!showIncidents.value && state === 'incident') {
      state = (showOccupancy.value && occupancySnapshot.value?.locations[locId]?.isOccupied)
        ? 'occupied'
        : 'empty';
      incidentCount = 0;
      highestSeverity = null;
    }

    // If occupancy layer is off, suppress occupied state
    if (!showOccupancy.value && state === 'occupied') {
      state = 'empty';
    }

    filteredMap[locId] = { state, incidentCount, highestSeverity, densityPct: overlay.densityPct };
  }
  return filteredMap;
});

// --- Heatmap ---
const heatmapByZone = computed<Record<string, HeatmapIntensity>>(() => {
  const intensityMap: Record<string, HeatmapIntensity> = {};
  for (const entry of heatmapEntries.value) {
    intensityMap[entry.zoneId] = entry.intensity;
  }
  return intensityMap;
});

function getZoneIntensity(zoneId: string): HeatmapIntensity | null {
  return heatmapByZone.value[zoneId] ?? null;
}

// --- Simulation ---
function runSimulation(units: number): SimulationResult | null {
  if (!layoutSnapshot.value || !occupancySnapshot.value || !incidentsSnapshot.value) return null;
  const simService = new SimulationService();
  const simulationResult = simService.simulate(
    { units },
    layoutSnapshot.value,
    occupancySnapshot.value,
  );

  // Re-evaluate recommendations including simulation feedback (R7, R8)
  recommendations.value = new RecommendationService().evaluate(
    layoutSnapshot.value,
    occupancySnapshot.value,
    incidentsSnapshot.value,
    simulationResult,
  );

  return simulationResult;
}

function clearSimulationRecommendations(): void {
  if (!layoutSnapshot.value || !occupancySnapshot.value || !incidentsSnapshot.value) return;
  recommendations.value = new RecommendationService().evaluate(
    layoutSnapshot.value,
    occupancySnapshot.value,
    incidentsSnapshot.value,
  );
}

// --- Data loading ---
onMounted(async () => {
  try {
    const layout = await new LayoutService().getLayoutSnapshot();
    layoutSnapshot.value = layout;

    const [occupancy, incidents] = await Promise.all([
      new OccupancyService().getOccupancySnapshot(layout),
      new IncidentsService().getIncidentsSnapshot(layout),
    ]);

    occupancySnapshot.value = occupancy;
    incidentsSnapshot.value = incidents;

    heatmapEntries.value = new HeatmapService().compute(occupancy, incidents);
    recommendations.value = new RecommendationService().evaluate(layout, occupancy, incidents);
  } catch (loadError) {
    error.value = loadError instanceof Error ? loadError.message : 'Failed to load warehouse data';
  } finally {
    loading.value = false;
  }
});
</script>

<style scoped>
.warehouse-grid {
  display: flex;
  flex-direction: column;
  gap: 20px;
  width: 100%;
}

.grid-status {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 48px 24px;
  border: 1px dashed #334155;
  border-radius: 12px;
  color: #94a3b8;
  font-size: 14px;
}

.grid-error {
  border-color: #7f1d1d;
  color: #fca5a5;
}

.status-icon {
  font-size: 18px;
}

/* --- Toolbar: legend + toggles --- */

.toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 8px 14px;
  border: 1px solid #1e293b;
  border-radius: 10px;
  background: rgba(15, 23, 42, 0.6);
  flex-wrap: wrap;
}

.legend-group {
  display: flex;
  align-items: center;
  gap: 14px;
  font-size: 12px;
  color: #94a3b8;
}

.legend-swatch {
  display: flex;
  align-items: center;
  gap: 5px;
}

.swatch {
  display: inline-block;
  width: 10px;
  height: 12px;
  border-radius: 2px;
  border: 1px solid transparent;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}

.swatch-empty {
  background: #0f172a;
  border-color: #334155;
}

.swatch-occupied {
  background: #0c2d48;
  border-color: #1e6091;
}

.swatch-incident {
  background: #422006;
  border-color: #b45309;
}

.swatch-blocked {
  background: #450a0a;
  border-color: #b91c1c;
}

.toggle-group {
  display: flex;
  align-items: center;
  gap: 12px;
}

.toggle-label {
  display: flex;
  align-items: center;
  gap: 5px;
  cursor: pointer;
  font-size: 12px;
  color: #94a3b8;
  user-select: none;
}

.toggle-label input[type="checkbox"] {
  accent-color: #3b82f6;
  cursor: pointer;
  width: 13px;
  height: 13px;
}

/* --- Warehouse sections --- */

.warehouse-section {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.warehouse-header {
  display: flex;
  align-items: baseline;
  gap: 10px;
  padding-bottom: 8px;
  border-bottom: 1px solid #1e293b;
}

.warehouse-title {
  margin: 0;
  font-size: 18px;
  font-weight: 700;
  color: #f1f5f9;
}

.warehouse-meta {
  font-size: 12px;
  color: #475569;
}

.zones-grid {
  display: flex;
  flex-direction: column;
  gap: 14px;
}
</style>
