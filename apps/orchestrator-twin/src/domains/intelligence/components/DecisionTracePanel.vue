<template>
  <article class="panel">
    <div class="panel-header">
      <h3 class="panel-title">Decision Traces</h3>
      <span v-if="traces.length > 0" class="panel-count count-default">{{ traces.length }}</span>
    </div>

    <div v-if="store.metrics" class="metrics-panel">
      <div class="metric-item"><div class="metric-val">{{ store.metrics.advisoryCount }}</div><div class="metric-lbl">Advisory</div></div>
      <div class="metric-item"><div class="metric-val">{{ store.metrics.actedUponCount }}</div><div class="metric-lbl">Acted Upon</div></div>
      <div class="metric-item"><div class="metric-val">{{ store.metrics.dismissedCount }}</div><div class="metric-lbl">Dismissed</div></div>
      <div class="metric-item item-critical"><div class="metric-val">{{ store.metrics.criticalCount }}</div><div class="metric-lbl">Critical</div></div>
    </div>

    <div class="trace-controls">
      <select v-model="filterStatus" @change="applyFilters" class="control-select">
        <option value="">All Statuses</option>
        <option value="advisory">Advisory</option>
        <option value="acknowledged">Acknowledged</option>
        <option value="acted_upon">Acted Upon</option>
        <option value="dismissed">Dismissed</option>
      </select>
      <select v-model="filterSeverity" @change="applyFilters" class="control-select">
        <option value="">All Severities</option>
        <option value="low">Low</option>
        <option value="medium">Medium</option>
        <option value="high">High</option>
        <option value="critical">Critical</option>
      </select>
      <button @click="resetFilters" class="control-btn" :disabled="!hasFilters">Reset</button>
    </div>

    <div v-if="store.isLoading && traces.length === 0" class="trace-empty">
      <span>Loading traces...</span>
    </div>
    <div v-else-if="store.error" class="trace-error">{{ store.error }}</div>
    <div v-else-if="traces.length === 0" class="trace-calm">
      <div class="calm-indicator"></div>
      <span>System calm. Active monitoring engaged.</span>
    </div>

    <ul v-else class="trace-list">
      <li
        v-for="trace in traces"
        :key="trace.id"
        class="trace-item"
        :class="['severity-' + trace.severity, 'status-' + trace.status, { 'is-new': store.justAppearedIds.has(trace.id) }]"
      >
        <div class="trace-top">
          <span class="trace-badge">{{ trace.severity }}</span>
          <span class="trace-domain">{{ trace.agentDomain }}</span>
        </div>

        <div class="trace-condition">{{ trace.detection }}</div>

        <div class="trace-detail">
          <div class="trace-row">
            <span class="trace-label trace-label-action">Action</span>
            <span class="trace-text trace-text-action">{{ trace.suggestion }}</span>
          </div>
          <div class="trace-actions">
            <button 
              @click="store.acknowledgeTrace(trace.id)" 
              :disabled="['acknowledged', 'acted_upon', 'dismissed'].includes(trace.status)"
              class="action-btn"
            >
              Acknowledge
            </button>
            <button 
              @click="store.actUponTrace(trace.id)" 
              :disabled="['acted_upon', 'dismissed'].includes(trace.status)"
              class="action-btn"
            >
              Act Upon
            </button>
            <button 
              @click="store.dismissTrace(trace.id)" 
              :disabled="['acted_upon', 'dismissed'].includes(trace.status)"
              class="action-btn"
            >
              Dismiss
            </button>
          </div>
        </div>
      </li>
    </ul>
  </article>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useDecisionTraceStore } from '../stores/useDecisionTraceStore';

const store = useDecisionTraceStore();
const traces = computed(() => store.traces);

const filterStatus = ref('');
const filterSeverity = ref('');

const hasFilters = computed(() => filterStatus.value !== '' || filterSeverity.value !== '');

function applyFilters() {
  store.fetchTraces({ 
    status: filterStatus.value || null, 
    severity: filterSeverity.value || null,
    agentDomain: null
  });
}

function resetFilters() {
  filterStatus.value = '';
  filterSeverity.value = '';
  store.fetchTraces({}, 'createdAt_desc');
}

onMounted(() => {
  store.fetchTraces();
  store.fetchMetrics();
});
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
  gap: 8px;
  margin-bottom: 12px;
}

.metrics-panel {
  display: flex;
  gap: 8px;
  margin-bottom: 14px;
}

.metric-item {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.08);
  padding: 6px 8px;
  border-radius: 6px;
  display: flex;
  flex-direction: column;
  align-items: center;
  flex: 1;
}

.item-critical {
  background: rgba(220, 38, 38, 0.05);
  border-color: rgba(220, 38, 38, 0.2);
}

.item-critical .metric-val {
  color: #ef4444;
}

.metric-val {
  font-size: 15px;
  font-weight: 700;
  color: #e2e8f0;
}

.metric-lbl {
  font-size: 9px;
  color: #94a3b8;
  text-transform: uppercase;
  margin-top: 2px;
}

.trace-controls {
  display: flex;
  gap: 8px;
  margin-bottom: 12px;
}

.control-select {
  background: rgba(15, 23, 42, 0.5);
  border: 1px solid #1e293b;
  color: #e2e8f0;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 11px;
  outline: none;
}

.control-btn {
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid #1e293b;
  color: #e2e8f0;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 11px;
  cursor: pointer;
}
.control-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.panel-title {
  margin: 0;
  font-size: 13px;
  font-weight: 600;
  color: #c084fc;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  flex: 1;
}

.panel-count {
  display: grid;
  place-items: center;
  min-width: 20px;
  height: 20px;
  border-radius: 10px;
  font-size: 11px;
  font-weight: 700;
  padding: 0 6px;
}

.count-default {
  background: rgba(192, 132, 252, 0.2);
  color: #c084fc;
}

.trace-empty {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
  color: #4ade80;
  padding: 8px 0;
}

.trace-calm {
  display: flex;
  align-items: center;
  gap: 12px;
  color: #94a3b8;
  font-size: 12px;
  padding: 12px;
  background: rgba(15, 23, 42, 0.3);
  border-radius: 6px;
  border: 1px dashed rgba(148, 163, 184, 0.3);
}

.calm-indicator {
  width: 8px;
  height: 8px;
  background-color: #10b981;
  border-radius: 50%;
  box-shadow: 0 0 8px #10b981;
  animation: calm-pulse 3s infinite;
}

@keyframes calm-pulse {
  0%, 100% { opacity: 0.5; box-shadow: 0 0 4px #10b981; }
  50% { opacity: 1; box-shadow: 0 0 10px #10b981; }
}

.trace-error {
  font-size: 12px;
  color: #ef4444;
  padding: 8px 0;
}
.trace-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.trace-item {
  display: flex;
  flex-direction: column;
  gap: 6px;
  padding: 10px 12px;
  border-radius: 8px;
  background: rgba(15, 23, 42, 0.5);
  border: 1px solid #1e293b;
  border-left: 4px solid #c084fc;
  transition: all 0.4s ease;
}

.trace-item.is-new {
  animation: pulse-glow 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse-glow {
  0%, 100% {
    box-shadow: 0 0 8px 1px rgba(192, 132, 252, 0.5);
  }
  50% {
    box-shadow: 0 0 2px 0 rgba(192, 132, 252, 0.1);
  }
}

.severity-critical { border-left-color: #ef4444; background: rgba(239, 68, 68, 0.08); }
.severity-high { border-left-color: #ea580c; background: rgba(234, 88, 12, 0.08); }
.severity-medium { border-left-color: #eab308; background: rgba(234, 179, 8, 0.08); }
.severity-low { border-left-color: #3b82f6; background: rgba(59, 130, 246, 0.08); }

.trace-item.severity-critical {
  border-left-width: 6px;
  border-color: rgba(239, 68, 68, 0.4);
  animation: critical-pulse 3s infinite;
}

@keyframes critical-pulse {
  0%, 100% { background: rgba(239, 68, 68, 0.08); }
  50% { background: rgba(239, 68, 68, 0.15); }
}

.trace-item.status-acknowledged {
  opacity: 0.85;
}

.trace-item.status-acted_upon {
  opacity: 0.75;
  border-left-color: #10b981;
}

.trace-item.status-dismissed {
  opacity: 0.6;
  filter: grayscale(80%);
}


.trace-top {
  display: flex;
  align-items: center;
  gap: 5px;
}

.trace-badge {
  padding: 1px 6px;
  border-radius: 3px;
  font-size: 9px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.severity-critical .trace-badge { background: rgba(239, 68, 68, 0.2); color: #fca5a5; }
.severity-high .trace-badge { background: rgba(234, 88, 12, 0.2); color: #fdba74; }
.severity-medium .trace-badge { background: rgba(234, 179, 8, 0.2); color: #fde047; }
.severity-low .trace-badge { background: rgba(59, 130, 246, 0.2); color: #93c5fd; }

.trace-domain {
  margin-left: auto;
  font-size: 9px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: #475569;
}

.trace-condition {
  font-size: 12px;
  line-height: 1.5;
  color: #e2e8f0;
  font-weight: 500;
}

.trace-detail {
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding-top: 6px;
  border-top: 1px solid rgba(30, 41, 59, 0.6);
}

.trace-row {
  display: flex;
  gap: 6px;
  font-size: 11px;
  line-height: 1.5;
}

.trace-label {
  flex-shrink: 0;
  font-weight: 700;
  text-transform: uppercase;
  font-size: 9px;
  letter-spacing: 0.04em;
  color: #64748b;
  padding-top: 2px;
  min-width: 38px;
}

.trace-label-action {
  color: #94a3b8;
}

.trace-text-action {
  color: #e2e8f0;
  font-weight: 500;
}

.trace-actions {
  display: flex;
  gap: 6px;
  margin-top: 8px;
  border-top: 1px solid rgba(255, 255, 255, 0.05);
  padding-top: 8px;
}

.action-btn {
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid #1e293b;
  color: #e2e8f0;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.action-btn:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.15);
}

.action-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}
</style>
