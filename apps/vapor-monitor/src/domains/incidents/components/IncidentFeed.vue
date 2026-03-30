<template>
  <div class="incident-feed">
    <h2 class="feed-title">
      <span class="feed-icon">⚠</span>
      Live Incident Feed
    </h2>
    <div v-if="incidents.length === 0" class="feed-empty">No active incidents reported.</div>
    <ul class="incident-list">
      <li v-for="incident in incidents" :key="incident.id" class="incident-item">
        <div class="incident-type">{{ incident.type }}</div>
        <div class="incident-desc">{{ incident.description }}</div>
        <div class="incident-time">{{ new Date(incident.time).toLocaleTimeString() }}</div>
      </li>
    </ul>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useMonitoringStore } from '@/domains/monitoring/stores/useMonitoringStore';

const store = useMonitoringStore();
const incidents = computed(() => store.recentIncidents);
</script>

<style scoped>
.feed-title {
  margin: 0 0 14px;
  font-size: 18px;
  font-weight: 700;
  color: #fca5a5;
  display: flex;
  align-items: center;
  gap: 8px;
}

.feed-icon {
  font-size: 16px;
}

.feed-empty {
  color: #64748b;
  font-size: 14px;
}

.incident-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  gap: 10px;
}

.incident-item {
  padding: 10px 12px;
  background: rgba(239, 68, 68, 0.08);
  border-left: 4px solid #ef4444;
  border-radius: 6px;
}

.incident-type {
  font-weight: 700;
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #f87171;
  margin-bottom: 4px;
}

.incident-desc {
  font-size: 14px;
  color: #e5e7eb;
  line-height: 1.4;
}

.incident-time {
  font-size: 11px;
  color: #64748b;
  margin-top: 4px;
}
</style>
