<template>
  <div class="app-shell">
    <header class="topbar">
      <div class="topbar-left">
        <p class="eyebrow">NexusWMS</p>
        <h1>Orchestrator Twin</h1>
      </div>
      <div class="topbar-right">
        <div class="status-pill">Phase 3 · Live</div>
      </div>
    </header>

    <main class="layout">
      <section class="scene-panel">
        <WarehouseGrid ref="gridRef" />
      </section>

      <aside class="insights-panel">
        <SimulationPanel :on-simulate="runWarehouseSimulation" />
        <RecommendationsPanel :recommendations="activeRecommendations" />
      </aside>
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import type { SimulationResult } from './domains/simulation/types';
import type { Recommendation } from './domains/recommendations/types';
import WarehouseGrid from './components/layout/WarehouseGrid.vue';
import SimulationPanel from './components/panels/SimulationPanel.vue';
import RecommendationsPanel from './components/panels/RecommendationsPanel.vue';

const gridRef = ref<InstanceType<typeof WarehouseGrid> | null>(null);

const activeRecommendations = computed<Recommendation[]>(() => {
  return gridRef.value?.recommendations ?? [];
});

function runWarehouseSimulation(units: number): SimulationResult | null {
  if (!gridRef.value) return null;
  return gridRef.value.runSimulation(units);
}
</script>

<style scoped>
:global(*) {
  box-sizing: border-box;
}

:global(body) {
  margin: 0;
  font-family:
    Inter,
    ui-sans-serif,
    system-ui,
    -apple-system,
    BlinkMacSystemFont,
    'Segoe UI',
    sans-serif;
  background: #020617;
  color: #e2e8f0;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

:global(#app) {
  min-height: 100vh;
}

.app-shell {
  min-height: 100vh;
  padding: 20px 24px 32px;
}

/* --- Topbar --- */

.topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 20px;
  padding-bottom: 16px;
  border-bottom: 1px solid #1e293b;
}

.topbar-left {
  display: flex;
  flex-direction: column;
}

.eyebrow {
  margin: 0 0 4px;
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  color: #64748b;
  font-weight: 600;
}

h1 {
  margin: 0;
  font-size: 26px;
  font-weight: 700;
  letter-spacing: -0.02em;
}

.status-pill {
  padding: 6px 14px;
  border: 1px solid #166534;
  border-radius: 999px;
  background: rgba(22, 101, 52, 0.15);
  color: #4ade80;
  font-size: 12px;
  font-weight: 600;
  letter-spacing: 0.03em;
}

/* --- Main layout --- */

.layout {
  display: grid;
  grid-template-columns: 1fr 340px;
  gap: 20px;
  align-items: start;
}

.scene-panel {
  border: 1px solid #1e293b;
  background: #0f172a;
  border-radius: 14px;
  padding: 16px;
  min-height: 72vh;
  box-shadow:
    0 1px 3px rgba(0, 0, 0, 0.2),
    0 4px 12px rgba(0, 0, 0, 0.1),
    inset 0 1px 0 rgba(255, 255, 255, 0.02);
}

.insights-panel {
  display: flex;
  flex-direction: column;
  gap: 14px;
  position: sticky;
  top: 20px;
}

/* --- Responsive --- */

@media (max-width: 1100px) {
  .layout {
    grid-template-columns: 1fr;
  }

  .insights-panel {
    position: static;
  }

  .scene-panel {
    min-height: 50vh;
  }
}

@media (max-width: 640px) {
  .topbar {
    flex-direction: column;
    align-items: flex-start;
  }

  .app-shell {
    padding: 16px;
  }
}
</style>
