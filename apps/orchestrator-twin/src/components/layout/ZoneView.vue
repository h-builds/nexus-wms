<template>
  <section class="zone-view" :class="heatmapClass">
    <!-- Zone header -->
    <div class="zone-header">
      <div class="zone-title-group">
        <div class="zone-marker"></div>
        <h3 class="zone-title">Zone {{ zone.id }}</h3>
      </div>
      <span class="zone-stats">{{ aisleCount }} aisle(s) · {{ totalRacks }} rack(s) · {{ totalBins }} bin(s)</span>
      <span v-if="heatmapIntensity" class="heatmap-badge" :class="'heat-' + heatmapIntensity">
        {{ heatmapIntensity }}
      </span>
    </div>

    <!-- Aisles -->
    <div class="zone-aisles">
      <div
        v-for="aisle in zone.aisles"
        :key="aisle.id"
        class="aisle-group"
      >
        <div class="aisle-header">
          <div class="aisle-marker"></div>
          <span class="aisle-label">Aisle {{ aisle.id }}</span>
        </div>
        <div class="aisle-lane">
          <!-- Lane floor texture -->
          <div class="lane-floor"></div>
          <div class="aisle-racks">
            <RackView
              v-for="rack in aisle.racks"
              :key="rack.id"
              :rack="rack"
              :overlays="overlays"
            />
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { SpatialZone } from '../../domains/layout/types';
import type { BinOverlay } from '../../domains/shared/binState';
import type { HeatmapIntensity } from '../../domains/heatmap/types';
import RackView from './RackView.vue';

const props = defineProps<{
  zone: SpatialZone;
  overlays: Record<string, BinOverlay>;
  heatmapIntensity?: HeatmapIntensity | null;
}>();

const heatmapClass = computed(() => {
  if (!props.heatmapIntensity) return '';
  return 'heatmap-' + props.heatmapIntensity;
});

const aisleCount = computed(() => props.zone.aisles.length);

const totalRacks = computed(() =>
  props.zone.aisles.reduce((sum, aisle) => sum + aisle.racks.length, 0),
);

const totalBins = computed(() =>
  props.zone.aisles.reduce(
    (sum, aisle) => sum + aisle.racks.reduce((rs, rack) => rs + rack.bins.length, 0),
    0,
  ),
);
</script>

<style scoped>
.zone-view {
  border: 1px solid #1e293b;
  border-radius: 12px;
  background: rgba(15, 23, 42, 0.5);
  padding: 18px;
  transition:
    border-color 0.2s ease,
    background-color 0.2s ease,
    box-shadow 0.3s ease,
    border-width 0.2s ease;
  box-shadow:
    0 1px 3px rgba(0, 0, 0, 0.2),
    inset 0 1px 0 rgba(255, 255, 255, 0.02);
}

/* --- Heatmap intensity: volume perception with inner depth --- */

.heatmap-low {
  border-color: #166534;
  background: linear-gradient(180deg, rgba(22, 101, 52, 0.04) 0%, rgba(22, 101, 52, 0.08) 100%);
  box-shadow:
    0 1px 4px rgba(0, 0, 0, 0.2),
    inset 0 -20px 40px -20px rgba(22, 101, 52, 0.06),
    inset 0 1px 0 rgba(74, 222, 128, 0.03);
}

.heatmap-medium {
  border-color: #a16207;
  background: linear-gradient(180deg, rgba(161, 98, 7, 0.05) 0%, rgba(161, 98, 7, 0.1) 100%);
  box-shadow:
    0 2px 8px rgba(0, 0, 0, 0.2),
    0 0 0 1px rgba(161, 98, 7, 0.08),
    inset 0 -30px 50px -20px rgba(161, 98, 7, 0.08),
    inset 0 1px 0 rgba(251, 191, 36, 0.03);
}

.heatmap-high {
  border-color: #b91c1c;
  border-width: 2px;
  background: linear-gradient(180deg, rgba(185, 28, 28, 0.06) 0%, rgba(185, 28, 28, 0.14) 100%);
  box-shadow:
    0 2px 8px rgba(185, 28, 28, 0.15),
    0 4px 16px rgba(0, 0, 0, 0.2),
    0 0 0 1px rgba(185, 28, 28, 0.1),
    inset 0 -40px 60px -20px rgba(185, 28, 28, 0.1),
    inset 0 1px 0 rgba(248, 113, 113, 0.04);
}

/* --- Zone header --- */

.zone-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
  padding-bottom: 12px;
  border-bottom: 1px solid rgba(30, 41, 59, 0.8);
}

.zone-title-group {
  display: flex;
  align-items: center;
  gap: 8px;
}

.zone-marker {
  width: 4px;
  height: 18px;
  border-radius: 2px;
  background: #475569;
}

.heatmap-low .zone-marker {
  background: #166534;
}

.heatmap-medium .zone-marker {
  background: #a16207;
}

.heatmap-high .zone-marker {
  background: #b91c1c;
}

.zone-title {
  margin: 0;
  font-size: 15px;
  font-weight: 700;
  color: #e2e8f0;
  letter-spacing: -0.01em;
}

.zone-stats {
  font-size: 11px;
  color: #475569;
}

.heatmap-badge {
  margin-left: auto;
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

.heat-low {
  background: rgba(22, 101, 52, 0.3);
  color: #4ade80;
}

.heat-medium {
  background: rgba(161, 98, 7, 0.3);
  color: #fbbf24;
}

.heat-high {
  background: rgba(185, 28, 28, 0.3);
  color: #fca5a5;
}

/* --- Aisles --- */

.zone-aisles {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.aisle-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.aisle-header {
  display: flex;
  align-items: center;
  gap: 6px;
}

.aisle-marker {
  width: 8px;
  height: 2px;
  border-radius: 1px;
  background: #334155;
}

.aisle-label {
  font-size: 11px;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

/* --- Aisle lane: the "floor" of the warehouse aisle --- */

.aisle-lane {
  position: relative;
  padding: 10px 12px 10px 14px;
  border-radius: 6px;
  background: rgba(2, 6, 23, 0.4);
  border: 1px solid rgba(30, 41, 59, 0.5);
}

.lane-floor {
  position: absolute;
  inset: 0;
  border-radius: 6px;
  background:
    repeating-linear-gradient(
      90deg,
      transparent 0px,
      transparent 80px,
      rgba(51, 65, 85, 0.08) 80px,
      rgba(51, 65, 85, 0.08) 81px
    );
  pointer-events: none;
}

.aisle-racks {
  position: relative;
  display: flex;
  flex-direction: column;
  gap: 10px;
}
</style>
