<template>
  <div class="decision-trace-feed">
    <h2 class="feed-title">
      <span class="feed-icon">🧠</span>
      Intelligence Traces
    </h2>
    <div v-if="store.metrics" class="metrics-strip">
      <div class="metric-card"><div class="metric-value">{{ store.metrics.advisoryCount }}</div><div class="metric-label">Advisory</div></div>
      <div class="metric-card"><div class="metric-value">{{ store.metrics.actedUponCount }}</div><div class="metric-label">Acted Upon</div></div>
      <div class="metric-card"><div class="metric-value">{{ store.metrics.dismissedCount }}</div><div class="metric-label">Dismissed</div></div>
      <div class="metric-card metric-critical"><div class="metric-value">{{ store.metrics.criticalCount }}</div><div class="metric-label">Critical</div></div>
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
    <div v-if="store.isLoading && traces.length === 0" class="feed-empty">Loading traces...</div>
    <div v-else-if="store.error" class="feed-error">{{ store.error }}</div>
    <div v-else-if="traces.length === 0" class="feed-calm">
      <div class="calm-indicator"></div>
      System calm. Active monitoring engaged.
    </div>
    <ul v-else class="trace-list">
      <li v-for="trace in traces" :key="trace.id" class="trace-item" :class="['severity-' + trace.severity, 'status-' + trace.status, { 'is-new': store.justAppearedIds.has(trace.id) }]">
        <div class="trace-header">
          <span class="trace-badge">{{ trace.severity }}</span>
          <span class="trace-type">{{ trace.traceType }}</span>
        </div>
        <div class="trace-detection"><strong>Detected:</strong> {{ trace.detection }}</div>
        <div class="trace-suggestion"><strong>Action:</strong> {{ trace.suggestion }}</div>
        <div class="trace-time">{{ new Date(trace.createdAt).toLocaleTimeString() }}</div>
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
      </li>
    </ul>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useDecisionTraceStore, type TraceSeverity, type TraceStatus } from '@/domains/intelligence/stores/useDecisionTraceStore';

const store = useDecisionTraceStore();
const traces = computed(() => store.traces);

const filterStatus = ref<TraceStatus | ''>('');
const filterSeverity = ref<TraceSeverity | ''>('');

const hasFilters = computed(() => filterStatus.value !== '' || filterSeverity.value !== '');

function applyFilters(): void {
  store.fetchTraces({
    status: filterStatus.value || null,
    severity: filterSeverity.value || null,
    agentDomain: null,
  });
}

function resetFilters(): void {
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
.feed-title {
  margin: 0 0 14px;
  font-size: 18px;
  font-weight: 700;
  color: #c084fc;
  display: flex;
  align-items: center;
  gap: 8px;
}

.trace-controls {
  display: flex;
  gap: 8px;
  margin-bottom: 12px;
}

.metrics-strip {
  display: flex;
  gap: 12px;
  margin-bottom: 16px;
}

.metric-card {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  padding: 8px 12px;
  border-radius: 6px;
  display: flex;
  flex-direction: column;
  align-items: center;
  flex: 1;
}

.metric-critical {
  background: rgba(239, 68, 68, 0.05);
  border-color: rgba(239, 68, 68, 0.2);
}

.metric-critical .metric-value {
  color: #ef4444;
}

.metric-value {
  font-size: 18px;
  font-weight: 700;
  color: #e2e8f0;
}

.metric-label {
  font-size: 10px;
  color: #94a3b8;
  text-transform: uppercase;
  margin-top: 2px;
}

.control-select {
  background: rgba(192, 132, 252, 0.08);
  border: 1px solid rgba(192, 132, 252, 0.2);
  color: #e2e8f0;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 11px;
  outline: none;
}

.control-select option {
  background-color: #1f2937;
  color: #e2e8f0;
}

.control-btn {
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: #e2e8f0;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 11px;
  cursor: pointer;
  transition: all 0.2s ease;
}
.control-btn:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.2);
}
.control-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.feed-icon {
  font-size: 16px;
}

.feed-calm {
  display: flex;
  align-items: center;
  gap: 12px;
  color: #94a3b8;
  font-size: 13px;
  padding: 16px 12px;
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

.feed-error {
  color: #ef4444;
  font-size: 14px;
}

.trace-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  gap: 10px;
}

.trace-item {
  padding: 10px 12px;
  background: rgba(192, 132, 252, 0.08);
  border-left: 4px solid #c084fc;
  border-radius: 6px;
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

.trace-header {
  display: flex;
  gap: 8px;
  margin-bottom: 6px;
  align-items: center;
}

.trace-badge {
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 9px;
  font-weight: 800;
  text-transform: uppercase;
  background: rgba(255, 255, 255, 0.1);
}

.severity-critical .trace-badge { background: rgba(239, 68, 68, 0.2); color: #fca5a5; }
.severity-high .trace-badge { background: rgba(234, 88, 12, 0.2); color: #fdba74; }
.severity-medium .trace-badge { background: rgba(234, 179, 8, 0.2); color: #fde047; }
.severity-low .trace-badge { background: rgba(59, 130, 246, 0.2); color: #93c5fd; }


.trace-type {
  font-weight: 700;
  font-size: 11px;
  text-transform: uppercase;
  color: #c084fc;
}

.trace-detection, .trace-suggestion {
  font-size: 13px;
  color: #e5e7eb;
  line-height: 1.4;
  margin-bottom: 4px;
}

.trace-time {
  font-size: 11px;
  color: #64748b;
  margin-top: 4px;
}

.trace-actions {
  display: flex;
  gap: 8px;
  margin-top: 10px;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  padding-top: 8px;
}

.action-btn {
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: #e2e8f0;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 11px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.action-btn:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.2);
}

.action-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
