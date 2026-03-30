<template>
  <div
    class="bin-cell"
    :class="[stateClass, { 'incident-distort': isIncident }]"
    :style="cellStyle"
    :title="tooltipText"
  >
    <!-- Top face: pseudo-3D lid -->
    <div class="bin-top"></div>

    <!-- Front face -->
    <div class="bin-front" :style="frontStyle">
      <span class="bin-label">{{ bin.bin || bin.level }}</span>

      <!-- Internal stacking layers (density-driven segmentation) -->
      <div
        v-if="overlay.state === 'occupied'"
        class="stacking"
        :style="stackingStyle"
      >
        <div class="stack-layer sl-4"></div>
        <div class="stack-layer sl-3"></div>
        <div class="stack-layer sl-2"></div>
        <div class="stack-layer sl-1"></div>
      </div>

      <!-- Incident fill with structural anomaly -->
      <div
        v-if="overlay.state === 'incident'"
        class="incident-fill"
        :class="{ 'incident-high': isHighSeverity }"
      >
        <div class="incident-crack"></div>
      </div>

      <!-- Blocked fill -->
      <div v-if="overlay.state === 'blocked'" class="blocked-fill">
        <div class="blocked-stripe"></div>
        <div class="blocked-stripe"></div>
      </div>
    </div>

    <!-- Incident badge -->
    <span v-if="overlay.incidentCount > 0" class="incident-badge">
      {{ overlay.incidentCount }}
    </span>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { SpatialBin } from '../../domains/layout/types';
import type { BinOverlay } from '../../domains/shared/binState';

const props = defineProps<{
  bin: SpatialBin;
  overlay: BinOverlay;
}>();

const stateClass = computed(() => 'state-' + props.overlay.state);

const isIncident = computed(() => props.overlay.state === 'incident');

/**
 * Height mapping function (v2 — wider range for stronger contrast).
 *
 * Maps zone density percentage (0–100) → visual scale factor (0.4–1.0).
 * The wider range (vs previous 0.6–1.0) makes low-vs-high density
 * distinguishable at a glance.
 *
 * Scale formula: 0.4 + (densityPct / 100) * 0.6
 *   0%   → 0.40 (visibly flat)
 *   50%  → 0.70 (medium height)
 *   80%  → 0.88 (near full)
 *   100% → 1.00 (full height)
 *
 * Empty bins render at minimized baseline (0.4).
 */
const heightScale = computed(() => {
  if (props.overlay.state === 'empty') return 0.4;
  const pct = Math.max(0, Math.min(100, props.overlay.densityPct));
  return 0.4 + (pct / 100) * 0.6;
});

/**
 * Shadow depth scales with height — denser zones cast heavier shadows.
 * Wider range intensifies the visual weight difference.
 */
const shadowDepth = computed(() => {
  const s = heightScale.value;
  const blur = Math.round(3 + s * 18);
  const yOff = Math.round(s * 6);
  const alpha1 = (0.08 + s * 0.25).toFixed(2);
  const alpha2 = (0.04 + s * 0.12).toFixed(2);
  return `0 ${yOff}px ${blur}px rgba(0,0,0,${alpha1}), 0 ${yOff * 2}px ${blur + 4}px rgba(0,0,0,${alpha2})`;
});

const isHighSeverity = computed(() => {
  return props.overlay.highestSeverity === 'high' || props.overlay.highestSeverity === 'critical';
});

/**
 * Cell-level style: translateY offset reinforces "rising" illusion.
 * Taller bins lift more: 0px at 0.4, -7px at 1.0.
 */
const cellStyle = computed(() => {
  const scale = heightScale.value;
  const lift = -Math.round((scale - 0.4) * 12);
  return {
    transform: `translateY(${lift}px)`,
  };
});

/**
 * Front face style: scaleY drives perceived height.
 * Incident bins get a slight skewY (structural anomaly).
 */
const frontStyle = computed(() => {
  const base = `scaleY(${heightScale.value})`;
  const skew = isIncident.value ? ' skewY(-1.5deg)' : '';
  return {
    transform: base + skew,
    transformOrigin: 'bottom',
    boxShadow: shadowDepth.value,
  };
});

/**
 * Internal stacking: visible layer count based on density.
 * Uses CSS opacity — higher density reveals more internal segments.
 *
 * Layers (bottom to top): sl-1 always visible, sl-2 at >25%,
 * sl-3 at >50%, sl-4 at >75%.
 */
const visibleLayers = computed(() => {
  const pct = props.overlay.densityPct;
  return Math.max(1, Math.min(4, Math.ceil(pct / 25)));
});

/**
 * Stacking container: height scales with density (20%–100% of available space).
 */
const stackingStyle = computed(() => {
  const pct = Math.max(0, Math.min(100, props.overlay.densityPct));
  const fillPct = 20 + (pct / 100) * 80;
  return {
    height: fillPct + '%',
    '--visible-layers': visibleLayers.value,
  } as Record<string, string | number>;
});

const tooltipText = computed(() => {
  const parts = [props.bin.label];

  if (props.overlay.state === 'blocked') {
    parts.push('[BLOCKED]');
  }
  if (props.overlay.incidentCount > 0) {
    parts.push(
      props.overlay.incidentCount +
        ' incident(s)' +
        (props.overlay.highestSeverity ? ' — ' + props.overlay.highestSeverity : ''),
    );
  }
  if (props.overlay.state === 'occupied') {
    parts.push('Occupied · Zone ' + Math.round(props.overlay.densityPct) + '%');
  }

  return parts.join(' · ');
});
</script>

<style scoped>
.bin-cell {
  position: relative;
  width: 56px;
  height: 58px;
  cursor: default;
  transform-style: preserve-3d;
  transition: transform 0.25s ease;
}

.bin-cell:hover {
  transform: translateY(-3px) !important;
}

/* === Top face: the "lid" === */

.bin-top {
  position: absolute;
  top: 0;
  left: 3px;
  right: 0;
  height: 7px;
  border-radius: 3px 3px 0 0;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.07) 0%, rgba(255, 255, 255, 0.01) 100%);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-bottom: none;
  transform: skewX(-6deg);
  pointer-events: none;
  transition: background 0.2s ease;
}

/* === Right side face: depth edge === */

.bin-cell::after {
  content: '';
  position: absolute;
  top: 4px;
  right: 0;
  width: 3px;
  bottom: 0;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.03) 0%, rgba(0, 0, 0, 0.18) 100%);
  border-radius: 0 3px 3px 0;
  pointer-events: none;
}

/* === Front face: main body === */

.bin-front {
  position: absolute;
  top: 5px;
  left: 0;
  right: 3px;
  bottom: 0;
  border: 1px solid #1e293b;
  border-radius: 4px;
  background: #0f172a;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-start;
  padding-top: 4px;
  transition:
    border-color 0.2s ease,
    background-color 0.2s ease,
    box-shadow 0.3s ease,
    transform 0.3s ease;
}

/* Inner edge highlight */
.bin-front::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 1px;
  background: linear-gradient(90deg, transparent 10%, rgba(255, 255, 255, 0.06) 50%, transparent 90%);
  pointer-events: none;
}

.bin-cell:hover .bin-front {
  border-color: #475569;
}

/* === Label === */

.bin-label {
  font-size: 10px;
  font-weight: 600;
  color: #64748b;
  letter-spacing: 0.02em;
  z-index: 1;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
}

/* === State: empty === */

.state-empty .bin-front {
  background: #0f172a;
  border-color: #1e293b;
}

.state-empty .bin-top {
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.03) 0%, transparent 100%);
}

/* === State: occupied === */

.state-occupied .bin-front {
  background: linear-gradient(180deg, #0c2d48 0%, #0a1e33 100%);
  border-color: #1e6091;
}

.state-occupied .bin-top {
  background: linear-gradient(180deg, rgba(56, 189, 248, 0.12) 0%, transparent 100%);
  border-color: rgba(56, 189, 248, 0.08);
}

.state-occupied .bin-label {
  color: #7dd3fc;
}

/* === State: incident (structural anomaly via parent class) === */

.state-incident .bin-front {
  background: linear-gradient(180deg, #422006 0%, #2d1a04 100%);
  border-color: #b45309;
}

.state-incident .bin-top {
  background: linear-gradient(180deg, rgba(251, 191, 36, 0.15) 0%, transparent 100%);
  border-color: rgba(251, 191, 36, 0.1);
}

/* Incident distortion: slight tilt on top face to feel "wrong" */
.incident-distort .bin-top {
  transform: skewX(-6deg) skewY(2deg);
}

/* Side edge on incident is cracked — darker, irregular */
.incident-distort::after {
  background: linear-gradient(180deg, rgba(251, 191, 36, 0.08) 0%, rgba(0, 0, 0, 0.25) 100%);
  width: 4px;
}

.state-incident .bin-label {
  color: #fdba74;
}

/* === State: blocked === */

.state-blocked .bin-front {
  background: linear-gradient(180deg, #450a0a 0%, #2d0606 100%);
  border-color: #b91c1c;
}

.state-blocked .bin-top {
  background: linear-gradient(180deg, rgba(248, 113, 113, 0.12) 0%, transparent 100%);
  border-color: rgba(248, 113, 113, 0.08);
}

.state-blocked .bin-label {
  color: #fca5a5;
}

/* === Internal stacking (density-driven layer segmentation) === */

.stacking {
  position: absolute;
  left: 3px;
  right: 3px;
  bottom: 2px;
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
  gap: 1px;
  transition: height 0.3s ease;
}

.stack-layer {
  border-radius: 1px;
  flex-shrink: 0;
  transition: opacity 0.3s ease, height 0.3s ease;
}

/* Bottom layer: always visible, heaviest */
.sl-1 {
  height: 6px;
  background: linear-gradient(180deg, rgba(56, 189, 248, 0.55) 0%, rgba(56, 189, 248, 0.4) 100%);
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.08);
}

/* Second layer: visible above 25% */
.sl-2 {
  height: 5px;
  background: linear-gradient(180deg, rgba(56, 189, 248, 0.4) 0%, rgba(56, 189, 248, 0.28) 100%);
  box-shadow: 0 1px 1px rgba(0, 0, 0, 0.2);
  opacity: var(--visible-layers, 1);
}

/* Third layer: visible above 50% */
.sl-3 {
  height: 4px;
  background: linear-gradient(180deg, rgba(56, 189, 248, 0.28) 0%, rgba(56, 189, 248, 0.18) 100%);
}

/* Top layer: visible above 75% — lightest */
.sl-4 {
  height: 3px;
  background: rgba(56, 189, 248, 0.12);
}

/* Layer visibility driven by JS-computed --visible-layers via opacity class logic */
/* We rely on stacking height % to control how many layers fit / are visible */

/* === Incident fill (structural anomaly) === */

.incident-fill {
  position: absolute;
  left: 3px;
  right: 3px;
  bottom: 2px;
  height: 14px;
  border-radius: 2px;
  background: rgba(251, 191, 36, 0.2);
  overflow: hidden;
  border-left: 2px solid rgba(251, 191, 36, 0.3);
}

/* Diagonal crack pattern simulating structural damage */
.incident-crack {
  width: 100%;
  height: 100%;
  background:
    linear-gradient(135deg, transparent 40%, rgba(251, 191, 36, 0.25) 45%, transparent 50%),
    linear-gradient(45deg, transparent 55%, rgba(251, 191, 36, 0.15) 60%, transparent 65%);
}

/* High-severity: stronger glow + expanded crack */
.incident-high {
  height: 20px;
  border-left: 3px solid rgba(251, 146, 60, 0.45);
  box-shadow: 0 0 10px rgba(251, 146, 60, 0.25);
}

.incident-high .incident-crack {
  background:
    linear-gradient(135deg, transparent 35%, rgba(251, 146, 60, 0.35) 42%, transparent 48%),
    linear-gradient(45deg, transparent 50%, rgba(251, 146, 60, 0.2) 58%, transparent 64%);
}

/* === Blocked fill === */

.blocked-fill {
  position: absolute;
  left: 3px;
  right: 3px;
  bottom: 2px;
  top: 18px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 3px;
  overflow: hidden;
}

.blocked-stripe {
  height: 2px;
  background: repeating-linear-gradient(
    90deg,
    rgba(248, 113, 113, 0.35) 0px,
    rgba(248, 113, 113, 0.35) 4px,
    transparent 4px,
    transparent 8px
  );
  border-radius: 1px;
}

/* === Incident badge === */

.incident-badge {
  position: absolute;
  top: -2px;
  right: -4px;
  display: grid;
  place-items: center;
  min-width: 15px;
  height: 15px;
  border-radius: 8px;
  background: #b45309;
  color: #fff;
  font-size: 9px;
  font-weight: 700;
  line-height: 1;
  padding: 0 3px;
  z-index: 2;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.4);
}

.state-blocked .incident-badge {
  background: #b91c1c;
}
</style>
