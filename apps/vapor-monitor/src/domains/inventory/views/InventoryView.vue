<template>
  <div class="inventory-view">
    <div class="header-section">
      <h2 class="title">Warehouse Inventory</h2>
      <div class="stats">
        <span class="stat-badge">Active Locations: {{ monitoringStore.activeLocationsCount }}</span>
        <span class="stat-badge bg-blue">Total Units: {{ monitoringStore.totalInventoryCount }}</span>
      </div>
    </div>
    
    <div class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th>Location ID</th>
            <th>Zone</th>
            <th class="text-right">Quantity on Hand</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="inventoryItems.length === 0">
            <td colspan="3" class="empty-state">No inventory data available.</td>
          </tr>
          <tr v-for="item in inventoryItems" :key="item.locationId">
            <td class="font-mono text-blue">{{ item.locationId }}</td>
            <td>
              <span class="zone-badge">{{ item.zone }}</span>
            </td>
            <td class="text-right font-bold">{{ item.quantity }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useMonitoringStore } from '@/domains/monitoring/stores/useMonitoringStore';
import { useEventStateStore } from '@/domains/events/stores/useEventStateStore';

const monitoringStore = useMonitoringStore();
const eventStore = useEventStateStore();

const inventoryItems = computed(() => {
  const items = [];
  const inventoryMap = eventStore.inventoryByLocation;
  for (const [locationId, quantity] of Object.entries(inventoryMap)) {
    if (quantity > 0) {
      const zone = monitoringStore.zoneOccupancy.find(z => locationId.startsWith(z.zone))?.zone || 'Unassigned';
      items.push({
        locationId,
        zone,
        quantity
      });
    }
  }
  return items.sort((a, b) => b.quantity - a.quantity);
});
</script>

<style scoped>
.inventory-view {
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
  color: #f8fafc;
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

.stat-badge.bg-blue {
  background: rgba(59, 130, 246, 0.1);
  border-color: rgba(59, 130, 246, 0.3);
  color: #60a5fa;
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

.text-right { text-align: right; }
.font-mono { font-family: ui-monospace, monospace; font-size: 13px; }
.text-blue { color: #60a5fa; }
.font-bold { font-weight: 700; }

.zone-badge {
  padding: 4px 8px;
  background: #334155;
  border-radius: 4px;
  font-size: 12px;
  color: #cbd5e1;
}

.empty-state {
  text-align: center;
  padding: 40px !important;
  color: #64748b !important;
  font-style: italic;
}
</style>
