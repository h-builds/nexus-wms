<template>
  <div class="movement-feed">
    <h2 class="feed-title">Inbound Feed</h2>
    <div v-if="movements.length === 0" class="feed-empty">No recent inbound movements.</div>
    <ul class="movement-list">
      <li v-for="movement in movements" :key="movement.id" class="movement-item">
        <div>
          <span class="movement-type">{{ movement.type }}</span>
          <div class="movement-time">{{ new Date(movement.time).toLocaleTimeString() }}</div>
        </div>
        <div class="movement-qty movement-qty-in">+{{ movement.quantity }}</div>
      </li>
    </ul>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useMonitoringStore } from '@/domains/monitoring/stores/useMonitoringStore';

const store = useMonitoringStore();
const movements = computed(() => store.recentInbound);
</script>

<style scoped>
.feed-title {
  margin: 0 0 14px;
  font-size: 18px;
  font-weight: 700;
  color: #e5e7eb;
}

.feed-empty {
  color: #64748b;
  font-size: 14px;
}

.movement-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  gap: 8px;
}

.movement-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 12px;
  border-left: 4px solid #10b981;
  border-radius: 6px;
  background: rgba(16, 185, 129, 0.06);
}

.movement-type {
  font-weight: 700;
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #34d399;
}

.movement-time {
  font-size: 11px;
  color: #64748b;
  margin-top: 3px;
}

.movement-qty {
  font-size: 18px;
  font-weight: 700;
}

.movement-qty-in {
  color: #10b981;
}
</style>
