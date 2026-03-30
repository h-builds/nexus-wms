<template>
  <article class="panel">
    <div class="panel-header">
      <h3 class="panel-title">Operational Intelligence</h3>
      <span v-if="criticalCount > 0" class="panel-count count-critical">{{ criticalCount }}</span>
      <span v-else-if="recommendations.length > 0" class="panel-count count-default">{{ recommendations.length }}</span>
    </div>

    <div v-if="recommendations.length === 0" class="rec-empty">
      <span class="rec-empty-icon">&#x2714;&#xFE0F;</span>
      <span>All zones operating normally — no action required.</span>
    </div>

    <ul v-else class="rec-list">
      <li
        v-for="(recommendation, i) in recommendations"
        :key="i"
        class="rec-item"
        :class="['rec-sev-' + recommendation.severity, 'rec-pri-' + recommendation.priority]"
      >
        <div class="rec-top">
          <span class="rec-badge">{{ recommendation.severity }}</span>
          <span class="rec-priority" :class="'pri-' + recommendation.priority">{{ recommendation.priority }}</span>
          <span class="rec-zone">{{ recommendation.zoneId === 'ALL' ? 'All Zones' : 'Zone ' + recommendation.zoneId }}</span>
          <span class="rec-category">{{ formatCategory(recommendation.category) }}</span>
        </div>

        <div class="rec-condition">{{ recommendation.condition }}</div>

        <div class="rec-detail">
          <div class="rec-row">
            <span class="rec-label">Risk</span>
            <span class="rec-text">{{ recommendation.risk }}</span>
          </div>
          <div class="rec-row">
            <span class="rec-label rec-label-action">Action</span>
            <span class="rec-text rec-text-action">{{ recommendation.action }}</span>
          </div>
        </div>
      </li>
    </ul>
  </article>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { Recommendation, RecommendationCategory } from '../../domains/recommendations/types';

const props = defineProps<{
  recommendations: Recommendation[];
}>();

const criticalCount = computed(() =>
  props.recommendations.filter((recommendation) => recommendation.severity === 'critical').length,
);

function formatCategory(category: RecommendationCategory): string {
  const categoryLabels: Record<RecommendationCategory, string> = {
    redistribution: 'Redistribution',
    inspection: 'Inspection',
    reallocation: 'Reallocation',
    escalation: 'Escalation',
    imbalance: 'Imbalance',
    simulation: 'Simulation',
  };
  return categoryLabels[category] ?? category;
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
  gap: 8px;
  margin-bottom: 12px;
}

.panel-title {
  margin: 0;
  font-size: 13px;
  font-weight: 600;
  color: #94a3b8;
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

.count-critical {
  background: rgba(185, 28, 28, 0.25);
  color: #fca5a5;
}

.count-default {
  background: rgba(161, 98, 7, 0.2);
  color: #fbbf24;
}

/* --- Empty state --- */

.rec-empty {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
  color: #4ade80;
  padding: 8px 0;
}

.rec-empty-icon {
  font-size: 14px;
}

/* --- List --- */

.rec-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.rec-item {
  display: flex;
  flex-direction: column;
  gap: 6px;
  padding: 10px 12px;
  border-radius: 8px;
  background: rgba(15, 23, 42, 0.5);
  border: 1px solid #1e293b;
}

/* --- Severity left-border --- */

.rec-sev-critical {
  border-color: rgba(185, 28, 28, 0.3);
  border-left: 3px solid #dc2626;
}

.rec-sev-warning {
  border-color: rgba(161, 98, 7, 0.25);
  border-left: 3px solid #d97706;
}

.rec-sev-info {
  border-left: 3px solid #2563eb;
}

/* --- Top row: badge + priority + zone + category --- */

.rec-top {
  display: flex;
  align-items: center;
  gap: 5px;
}

.rec-badge {
  padding: 1px 6px;
  border-radius: 3px;
  font-size: 9px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.rec-sev-critical .rec-badge {
  background: rgba(185, 28, 28, 0.35);
  color: #fca5a5;
}

.rec-sev-warning .rec-badge {
  background: rgba(161, 98, 7, 0.35);
  color: #fbbf24;
}

.rec-sev-info .rec-badge {
  background: rgba(30, 64, 175, 0.35);
  color: #93c5fd;
}

/* --- Priority indicator --- */

.rec-priority {
  padding: 1px 5px;
  border-radius: 3px;
  font-size: 8px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  border: 1px solid transparent;
}

.pri-immediate {
  color: #fca5a5;
  border-color: rgba(185, 28, 28, 0.25);
  background: rgba(185, 28, 28, 0.1);
}

.pri-next {
  color: #fbbf24;
  border-color: rgba(161, 98, 7, 0.2);
  background: rgba(161, 98, 7, 0.08);
}

.pri-monitor {
  color: #64748b;
  border-color: rgba(100, 116, 139, 0.2);
  background: rgba(100, 116, 139, 0.06);
}

.rec-zone {
  font-size: 11px;
  font-weight: 700;
  color: #cbd5e1;
}

.rec-category {
  margin-left: auto;
  font-size: 9px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: #475569;
}

/* --- Condition line --- */

.rec-condition {
  font-size: 12px;
  line-height: 1.5;
  color: #e2e8f0;
  font-weight: 500;
}

/* --- Detail rows (risk + action) --- */

.rec-detail {
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding-top: 6px;
  border-top: 1px solid rgba(30, 41, 59, 0.6);
}

.rec-row {
  display: flex;
  gap: 6px;
  font-size: 11px;
  line-height: 1.5;
}

.rec-label {
  flex-shrink: 0;
  font-weight: 700;
  text-transform: uppercase;
  font-size: 9px;
  letter-spacing: 0.04em;
  color: #64748b;
  padding-top: 2px;
  min-width: 38px;
}

.rec-label-action {
  color: #94a3b8;
}

.rec-text {
  color: #94a3b8;
}

.rec-text-action {
  color: #e2e8f0;
  font-weight: 500;
}
</style>
