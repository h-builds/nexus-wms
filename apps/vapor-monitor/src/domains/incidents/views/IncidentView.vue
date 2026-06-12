<template>
  <div class="incident-view">
    <div class="header-section">
      <h2 class="title">Active Incidents</h2>
      <div class="stats">
        <span class="stat-badge" :class="{ 'bg-red': openIncidents.length > 0 }">
          Open Incidents: {{ openIncidents.length }}
        </span>
      </div>
    </div>
    
    <div class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th>Incident ID</th>
            <th>Type</th>
            <th>Location</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="openIncidents.length === 0">
            <td colspan="3" class="empty-state">No open incidents currently.</td>
          </tr>
          <tr v-for="incident in openIncidents" :key="incident.id">
            <td class="font-mono">{{ incident.id }}</td>
            <td>
              <span class="type-badge">{{ incident.type }}</span>
            </td>
            <td class="font-mono text-zinc">{{ incident.locationId }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useEventStateStore } from '@/domains/events/stores/useEventStateStore';
import { useMonitoringStore } from '@/domains/monitoring/stores/useMonitoringStore';

const eventStore = useEventStateStore();
const monitoringStore = useMonitoringStore();

const openIncidents = computed(() => {
  const incidentsList = [];
  const openIds = Array.from(eventStore._rawStateRef.openIncidents);
  
  // Cross reference with recentIncidents to get details since state store only keeps IDs
  for (const id of openIds) {
    const feedItem = monitoringStore.recentIncidents.find(inc => inc.id === id);
    
    let locationId = 'Unknown';
    for (const [locId, incSet] of Object.entries(eventStore._rawStateRef.openIncidentsByLocation)) {
      if ((incSet as Set<string>).has(id)) {
        locationId = locId;
        break;
      }
    }
    
    incidentsList.push({
      id,
      type: feedItem?.type || 'Unknown',
      locationId
    });
  }
  
  return incidentsList;
});
</script>

<style scoped>
.incident-view {
  animation: fade-in 0.3s ease-out;
}

@keyframes fade-in {
  from { opacity: 0; transform: translateY(4px); }
  to { opacity: 1; transform: translateY(0); }
}

.header-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.title {
  font-size: 24px;
  margin: 0;
  color: #fca5a5;
}

.stats {
  display: flex;
  gap: 12px;
}

.stat-badge {
  padding: 6px 12px;
  background: #1e293b;
  border: 1px solid #334155;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 600;
  color: #cbd5e1;
}

.stat-badge.bg-red {
  background: rgba(239, 68, 68, 0.1);
  border-color: rgba(239, 68, 68, 0.3);
  color: #f87171;
}

.table-container {
  background: #111827;
  border: 1px solid #1f2937;
  border-radius: 12px;
  overflow: hidden;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th {
  background: #1e293b;
  color: #94a3b8;
  text-transform: uppercase;
  font-size: 11px;
  letter-spacing: 0.5px;
  padding: 12px 16px;
  text-align: left;
  border-bottom: 1px solid #334155;
}

.data-table td {
  padding: 16px;
  border-bottom: 1px solid #1f2937;
  color: #e2e8f0;
  font-size: 14px;
}

.data-table tr:last-child td {
  border-bottom: none;
}

.data-table tr:hover td {
  background: rgba(255, 255, 255, 0.02);
}

.font-mono { font-family: ui-monospace, monospace; font-size: 13px; }
.text-zinc { color: #94a3b8; }

.type-badge {
  padding: 4px 8px;
  background: rgba(239, 68, 68, 0.1);
  border: 1px solid rgba(239, 68, 68, 0.2);
  border-radius: 4px;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  color: #f87171;
}

.empty-state {
  text-align: center;
  padding: 40px !important;
  color: #64748b !important;
  font-style: italic;
}
</style>
