<template>
  <div class="rack-view">
    <!-- Rack frame: physical rack structure -->
    <div class="rack-frame">
      <!-- Left upright -->
      <div class="rack-upright rack-upright-left"></div>

      <!-- Rack content area -->
      <div class="rack-body">
        <div class="rack-header">
          <span class="rack-id">{{ rack.id }}</span>
          <span class="bin-count">{{ rack.bins.length }} slot(s)</span>
        </div>

        <!-- Shelf rail -->
        <div class="rack-shelf"></div>

        <!-- Bins sit on the shelf -->
        <div class="rack-bins">
          <BinCell
            v-for="bin in rack.bins"
            :key="bin.locationId"
            :bin="bin"
            :overlay="getOverlay(bin.locationId)"
          />
        </div>

        <!-- Floor shadow -->
        <div class="rack-floor"></div>
      </div>

      <!-- Right upright -->
      <div class="rack-upright rack-upright-right"></div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { SpatialRack } from '../../domains/layout/types';
import type { BinOverlay } from '../../domains/shared/binState';
import BinCell from './BinCell.vue';

const props = defineProps<{
  rack: SpatialRack;
  overlays: Record<string, BinOverlay>;
}>();

const DEFAULT_OVERLAY: BinOverlay = {
  state: 'empty',
  incidentCount: 0,
  highestSeverity: null,
  densityPct: 0,
};

function getOverlay(locationId: string): BinOverlay {
  return props.overlays[locationId] || DEFAULT_OVERLAY;
}
</script>

<style scoped>
.rack-view {
  display: flex;
  flex-direction: column;
}

/* === Rack frame: simulates physical uprights === */

.rack-frame {
  display: flex;
  align-items: stretch;
  position: relative;
}

.rack-upright {
  width: 4px;
  background: linear-gradient(180deg, #334155 0%, #1e293b 60%, #0f172a 100%);
  border-radius: 2px;
  flex-shrink: 0;
  box-shadow:
    inset -1px 0 0 rgba(255, 255, 255, 0.04),
    1px 0 3px rgba(0, 0, 0, 0.2);
}

.rack-upright-left {
  margin-right: 2px;
}

.rack-upright-right {
  margin-left: 2px;
}

/* === Rack body: container for header, shelf, and bins === */

.rack-body {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0;
  min-width: 0;
  background: rgba(15, 23, 42, 0.35);
  border-radius: 4px;
  padding: 6px 8px 6px;
  box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.15);
}

.rack-header {
  display: flex;
  align-items: baseline;
  gap: 6px;
  margin-bottom: 4px;
}

.rack-id {
  font-size: 11px;
  font-weight: 700;
  color: #94a3b8;
  letter-spacing: 0.03em;
}

.bin-count {
  font-size: 10px;
  color: #475569;
}

/* === Shelf rail: horizontal beam the bins sit on === */

.rack-shelf {
  height: 2px;
  background: linear-gradient(90deg, #334155 0%, #475569 50%, #334155 100%);
  border-radius: 1px;
  margin-bottom: 6px;
  box-shadow:
    0 1px 2px rgba(0, 0, 0, 0.3),
    0 0 0 0.5px rgba(255, 255, 255, 0.03);
  position: relative;
}

.rack-shelf::after {
  content: '';
  position: absolute;
  top: -1px;
  left: 10%;
  right: 10%;
  height: 1px;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 1px;
}

/* === Bin row === */

.rack-bins {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  padding: 2px 0;
}

/* === Floor shadow === */

.rack-floor {
  height: 4px;
  margin-top: 4px;
  background: linear-gradient(180deg, rgba(0, 0, 0, 0.12) 0%, transparent 100%);
  border-radius: 0 0 4px 4px;
}
</style>
