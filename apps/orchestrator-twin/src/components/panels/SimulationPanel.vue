<template>
  <article class="panel">
    <div class="panel-header">
      <h3 class="panel-title">Inbound Simulation</h3>
      <span class="panel-badge">What-if</span>
    </div>

    <div class="sim-row">
      <input
        v-model.number="units"
        type="number"
        min="1"
        placeholder="Units to place"
        class="sim-input"
        @keyup.enter="executeSimulation"
      />
      <button class="sim-btn" @click="executeSimulation" :disabled="units < 1">
        Run
      </button>
    </div>

    <div v-if="errorMessage" class="sim-status-error">
      <span class="error-icon">&#x26A0;&#xFE0F;</span>
      <span>{{ errorMessage }}</span>
    </div>

    <div v-else-if="simulationSummary" class="sim-result">
      <div class="sim-stat">
        <span class="sim-stat-label">Allocated</span>
        <strong class="sim-stat-value">{{ simulationSummary.totalUnitsAllocated }}</strong>
      </div>
      <div class="sim-stat">
        <span class="sim-stat-label">Unplaced</span>
        <strong
          class="sim-stat-value"
          :class="{ 'stat-warn': simulationSummary.totalUnitsUnplaced > 0 }"
        >
          {{ simulationSummary.totalUnitsUnplaced }}
        </strong>
      </div>
      <div class="sim-stat">
        <span class="sim-stat-label">Zones affected</span>
        <strong
          class="sim-stat-value"
          :class="{ 'stat-warn': zonesLabel.isConstrained }"
        >
          {{ zonesLabel.text }}
        </strong>
      </div>
    </div>

    <p v-else class="sim-hint">Enter a unit count and run a hypothetical inbound allocation.</p>
  </article>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import type { SimulationResult } from '../../domains/simulation/types';

const props = defineProps<{
  onSimulate: (units: number) => SimulationResult | null;
}>();

const units = ref<number>(1);
const simulationSummary = ref<SimulationResult | null>(null);
const errorMessage = ref<string | null>(null);

/**
 * Produces a capacity-aware label for the "Zones affected" stat.
 * Avoids showing "None" when the system is clearly constrained.
 */
const zonesLabel = computed(() => {
  if (!simulationSummary.value) return { text: '', isConstrained: false };

  if (simulationSummary.value.affectedZones.length > 0) {
    return { text: simulationSummary.value.affectedZones.join(', '), isConstrained: false };
  }

  if (simulationSummary.value.totalUnitsUnplaced > 0) {
    return { text: 'Capacity exhausted', isConstrained: true };
  }

  return { text: 'None', isConstrained: false };
});

function executeSimulation() {
  errorMessage.value = null;
  if (units.value < 1) {
    errorMessage.value = 'Unit count must be greater than zero.';
    return;
  }
  
  const simulationResult = props.onSimulate(units.value);
  
  if (!simulationResult) {
    errorMessage.value = 'Simulation failed: Operation context is not ready.';
    return;
  }
  
  simulationSummary.value = simulationResult;
}
</script>

<style scoped>
.panel {
  border: 1px solid #1e293b;
  background: #0f172a;
  border-radius: 14px;
  padding: 16px;
}

.panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}

.panel-title {
  margin: 0;
  font-size: 13px;
  font-weight: 600;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.panel-badge {
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  background: rgba(30, 64, 175, 0.2);
  color: #93c5fd;
}

.sim-row {
  display: flex;
  gap: 8px;
}

.sim-input {
  flex: 1;
  padding: 9px 12px;
  border: 1px solid #334155;
  border-radius: 8px;
  background: #020617;
  color: #e2e8f0;
  font-size: 14px;
  outline: none;
  transition: border-color 0.15s ease;
}

.sim-input:focus {
  border-color: #3b82f6;
}

.sim-input::placeholder {
  color: #475569;
}

.sim-btn {
  padding: 9px 20px;
  border: none;
  border-radius: 8px;
  background: #1e40af;
  color: #e2e8f0;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.15s ease;
}

.sim-btn:hover:not(:disabled) {
  background: #2563eb;
}

.sim-btn:disabled {
  opacity: 0.35;
  cursor: not-allowed;
}

.sim-result {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-top: 14px;
  padding-top: 14px;
  border-top: 1px solid #1e293b;
}

.sim-stat {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
}

.sim-stat-label {
  font-size: 12px;
  color: #64748b;
}

.sim-stat-value {
  font-size: 15px;
  font-weight: 600;
}

.stat-warn {
  color: #fbbf24;
}

.sim-hint {
  margin: 8px 0 0;
  font-size: 12px;
  color: #475569;
  line-height: 1.5;
}

.sim-status-error {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-top: 14px;
  padding: 10px 12px;
  border-radius: 6px;
  background: rgba(127, 29, 29, 0.2);
  border: 1px solid rgba(185, 28, 28, 0.4);
  color: #fca5a5;
  font-size: 12px;
  line-height: 1.4;
}
</style>
