<script setup lang="ts">
import { onMounted } from 'vue';
import { useMonitoringStore } from '@/domains/monitoring/stores/useMonitoringStore';
import InboundFeed from '@/domains/movements/components/InboundFeed.vue';
import OutboundFeed from '@/domains/movements/components/OutboundFeed.vue';
import IncidentFeed from '@/domains/incidents/components/IncidentFeed.vue';
import ZoneOccupancy from '@/domains/locations/components/ZoneOccupancy.vue';

const store = useMonitoringStore();

onMounted(() => {
  store.fetchInitialData();
});
</script>

<template>
  <div class="app-shell">
    <header class="topbar">
      <div>
        <p class="eyebrow">NexusWMS</p>
        <h1>Vapor Monitor</h1>
      </div>
      <div 
        class="status-pill" 
        :class="{ 'status-error': store.error, 'status-loading': store.isLoading }"
      >
        {{ store.error ? 'Connection Failed' : (store.isLoading ? 'Syncing...' : 'Operational Realtime Feed Active') }}
      </div>
    </header>

    <main class="content">
      <aside class="sidebar">
        <nav>
          <ul>
            <li class="active">Monitoring</li>
            <li class="disabled" title="Coming in Phase 3">
              Inventory
              <span class="phase-label">Phase 3</span>
            </li>
            <li class="disabled" title="Coming in Phase 3">
              Incidents
              <span class="phase-label">Phase 3</span>
            </li>
          </ul>
        </nav>
      </aside>

      <section class="main-panel">
        
        <div v-if="store.error" class="error-banner">
          {{ store.error }}
        </div>

        <div v-else-if="store.isLoading" class="loading-state">
          Connecting to warehouse streams...
        </div>

        <div v-else class="dashboard-content">
          <div class="card-grid">
            <article class="card">
              <p class="label">Total Inventory</p>
              <strong>{{ store.totalInventoryCount }}</strong>
              <p class="card-context">units across {{ store.activeLocationsCount }} locations</p>
            </article>
            <article class="card">
              <p class="label">Open Incidents</p>
              <strong class="text-red">{{ store.openIncidentsCount }}</strong>
            </article>
            <article class="card">
              <p class="label">Differences</p>
              <strong class="text-yellow">{{ store.inventoryDifferences }}</strong>
            </article>
          </div>

          <!-- Structural Occupancy Core -->
          <article class="panel mb-section">
            <ZoneOccupancy />
          </article>

          <!-- Bounded Feeds -->
          <div class="feeds-grid">
            <article class="panel feed-inbound">
              <InboundFeed />
            </article>
            <article class="panel feed-outbound">
              <OutboundFeed />
            </article>
            <article class="panel feed-incident">
              <IncidentFeed />
            </article>
          </div>
        </div>

      </section>
    </main>
  </div>
</template>

<style scoped>
:global(*) {
  box-sizing: border-box;
}

:global(body) {
  margin: 0;
  font-family: Inter, system-ui, sans-serif;
  background: #0b1020;
  color: #e5e7eb;
}

.app-shell {
  min-height: 100vh;
  padding: 24px;
}

.topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 24px;
}

.eyebrow {
  margin: 0 0 6px;
  font-size: 12px;
  text-transform: uppercase;
  color: #94a3b8;
}

h1 {
  margin: 0;
  font-size: 32px;
}

.status-pill {
  padding: 10px 14px;
  border: 1px solid #10b981;
  border-radius: 999px;
  background: rgba(16, 185, 129, 0.1);
  color: #10b981;
  font-size: 14px;
}

.content {
  display: grid;
  grid-template-columns: 240px 1fr;
  gap: 24px;
}

.sidebar, .panel, .card {
  border: 1px solid #1f2937;
  background: #111827;
  border-radius: 16px;
  padding: 18px;
}

.sidebar ul {
  list-style: none;
  padding: 0;
  margin: 0;
  display: grid;
  gap: 12px;
}

.sidebar li {
  padding: 10px 12px;
  border-radius: 10px;
  background: #0f172a;
  color: #64748b;
}

.sidebar li.active {
  background: #1e293b;
  color: #f8fafc;
}

.sidebar li.disabled {
  opacity: 0.4;
  pointer-events: none;
  user-select: none;
  cursor: default;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.phase-label {
  font-size: 10px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #475569;
  background: #1e293b;
  padding: 2px 6px;
  border-radius: 4px;
}

.main-panel {
  display: grid;
  gap: 24px;
}

.card-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 16px;
}

.label {
  margin: 0 0 8px;
  font-size: 13px;
  color: #94a3b8;
}

.card strong {
  font-size: 28px;
}

.text-red { color: #ef4444; }
.text-yellow { color: #f59e0b; }

.mb-section {
  margin-bottom: 24px;
}

.dashboard-content {
  display: grid;
  gap: 24px;
}

.feeds-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 20px;
}

.feed-inbound {
  border-top: 4px solid #10b981;
}

.feed-outbound {
  border-top: 4px solid #3b82f6;
}

.feed-incident {
  border-top: 4px solid #ef4444;
  background: rgba(239, 68, 68, 0.04);
  box-shadow: inset 0 0 0 1px rgba(239, 68, 68, 0.15);
}

.card-context {
  margin: 6px 0 0;
  font-size: 12px;
  color: #64748b;
  font-weight: 400;
}

.error-banner {
  padding: 16px;
  background: rgba(239, 68, 68, 0.1);
  border: 1px solid #ef4444;
  color: #ef4444;
  border-radius: 8px;
  text-align: center;
}

.loading-state {
  padding: 40px;
  text-align: center;
  color: #94a3b8;
  font-style: italic;
}

.status-error {
  border-color: #ef4444;
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
}

.status-loading {
  border-color: #f59e0b;
  background: rgba(245, 158, 11, 0.1);
  color: #f59e0b;
}
</style>
