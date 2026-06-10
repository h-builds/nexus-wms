<template>
  <div class="warehouse-grid">
    <template v-if="isLayoutLoading">
      <div class="grid-status">
        <span class="status-icon">&#x23F3;</span>
        <span>Loading warehouse data&hellip;</span>
      </div>
    </template>

    <template v-else-if="layoutLoadError">
      <div class="grid-status grid-error">
        <span class="status-icon">&#x26A0;&#xFE0F;</span>
        <span>{{ layoutLoadError }}</span>
      </div>
    </template>

    <template v-else-if="layoutSnapshot && layoutSnapshot.warehouses.length > 0">
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
import { onMounted } from 'vue';
import { useWarehouseGrid } from '../composables/useWarehouseGrid';
import ZoneView from '../../../components/layout/ZoneView.vue';

const {
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
} = useWarehouseGrid();

defineExpose({
  recommendations,
  layoutSnapshot,
  runSimulation,
  clearSimulationRecommendations,
});

onMounted(() => {
  loadWarehouseData();
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
