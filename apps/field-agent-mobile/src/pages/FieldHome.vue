<script setup lang="ts">
import { useIncidentsStore } from '@/stores/useIncidentsStore'
import { useInventoryStore } from '@/stores/useInventoryStore'
import { computed, ref, onMounted } from 'vue'
import { syncQueueManager } from '@/offline/SyncQueue'

const incidentsStore = useIncidentsStore()
const inventoryStore = useInventoryStore()

const syncQueueLength = ref(0)

onMounted(async () => {
  const queue = await syncQueueManager.getQueue()
  syncQueueLength.value = queue.length
})
</script>

<template>
  <q-page class="q-pa-md">
    <div class="text-h5 q-mb-md">Field Agent Home</div>
    
    <div class="row q-col-gutter-md">
      <div class="col-12 col-md-4">
        <q-card>
          <q-card-section>
            <div class="text-h6">Offline Drafts</div>
            <div class="text-subtitle2" v-if="syncQueueLength === 0">No pending drafts</div>
            <div class="text-h4 text-warning" v-else>{{ syncQueueLength }}</div>
          </q-card-section>
          
          <q-card-actions align="right" v-if="syncQueueLength > 0">
            <q-btn flat color="grey" label="View Queue (Phase 2)" disable />
          </q-card-actions>
        </q-card>
      </div>

      <div class="col-12 col-md-4">
        <q-card>
          <q-card-section>
            <div class="text-h6">Quick Actions</div>
          </q-card-section>
          <q-card-actions vertical>
            <q-btn flat align="left" icon="search" color="primary" label="Lookup Product" to="/product-lookup" />
            <q-btn flat align="left" icon="warning" color="warning" label="Report Incident" to="/incidents/report" />
            <q-btn flat align="left" icon="swap_horiz" color="secondary" label="Register Movement" to="/movements/register" />
          </q-card-actions>
        </q-card>
      </div>
    </div>
  </q-page>
</template>

<style scoped>
</style>
