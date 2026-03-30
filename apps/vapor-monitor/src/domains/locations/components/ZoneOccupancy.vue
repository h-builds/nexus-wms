<template>
  <div class="zone-occupancy">
    <h2 class="zone-title">Zone Occupancy</h2>
    <div v-if="zones.length === 0" class="zone-empty">No zones active.</div>
    <div class="zone-list">
      <div v-for="zone in zones" :key="zone.zone" class="zone-row">
        <div class="zone-header">
          <span class="zone-name">Zone {{ zone.zone }}</span>
          <span class="zone-stats">{{ zone.occupied }} / {{ zone.total }} ({{ zone.percentage }}%)</span>
        </div>
        <div class="bar-track">
          <div 
            class="bar-fill"
            :class="[
              zone.percentage > 90 ? 'bar-high' : 
              zone.percentage > 70 ? 'bar-medium' : 'bar-low'
            ]"
            :style="{ width: `${zone.percentage}%` }"
          ></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useMonitoringStore } from '@/domains/monitoring/stores/useMonitoringStore';

const store = useMonitoringStore();
const zones = computed(() => store.zoneOccupancy);
</script>

<style scoped>
.zone-title {
  margin: 0 0 16px;
  font-size: 18px;
  font-weight: 700;
  color: #e5e7eb;
}

.zone-empty {
  color: #64748b;
  font-size: 14px;
}

.zone-list {
  display: grid;
  gap: 14px;
}

.zone-row {
  background: #0f172a;
  border: 1px solid #1e293b;
  border-radius: 10px;
  padding: 12px 14px;
}

.zone-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.zone-name {
  font-weight: 600;
  color: #e5e7eb;
  font-size: 14px;
}

.zone-stats {
  font-size: 13px;
  font-weight: 600;
  color: #94a3b8;
}

.bar-track {
  width: 100%;
  height: 8px;
  background: #1e293b;
  border-radius: 999px;
  overflow: hidden;
}

.bar-fill {
  height: 100%;
  border-radius: 999px;
  transition: width 0.5s ease;
}

.bar-low {
  background: #10b981;
}

.bar-medium {
  background: #f59e0b;
}

.bar-high {
  background: #ef4444;
}
</style>
