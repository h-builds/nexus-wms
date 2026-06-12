<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { syncQueueManager, type QueuedRequest } from '@/offline/SyncQueue'
import api from '@/services/api'

const router = useRouter()
const $q = useQuasar()

const queue = ref<QueuedRequest[]>([])
const isSyncing = ref(false)

const loadQueue = async () => {
  queue.value = await syncQueueManager.getQueue()
}

onMounted(() => {
  loadQueue()
})

const syncQueue = async () => {
  if (queue.value.length === 0) return

  isSyncing.value = true
  let successCount = 0
  let failCount = 0

  for (const item of queue.value) {
    try {
      if (item.method.toLowerCase() === 'post') {
        await api.post(item.url, item.payload, {
          headers: { 'Idempotency-Key': item.idempotencyKey }
        })
      } else if (item.method.toLowerCase() === 'patch') {
        await api.patch(item.url, item.payload, {
          headers: { 'Idempotency-Key': item.idempotencyKey }
        })
      }
      
      await syncQueueManager.dequeue(item.idempotencyKey)
      successCount++
    } catch (err) {
      console.error('Failed to sync item:', item, err)
      failCount++
    }
  }

  await loadQueue()
  isSyncing.value = false

  if (failCount === 0) {
    $q.notify({ type: 'positive', message: `Successfully synced ${successCount} items` })
  } else {
    $q.notify({ type: 'warning', message: `Synced ${successCount} items, but ${failCount} failed` })
  }
}

const clearQueue = async () => {
  await syncQueueManager.clearQueue()
  await loadQueue()
  $q.notify({ type: 'info', message: 'Queue cleared' })
}
</script>

<template>
  <q-page class="q-pa-md">
    <div class="row items-center q-mb-md">
      <q-btn flat round dense icon="arrow_back" @click="router.back()" class="q-mr-sm" />
      <div class="text-h5">Offline Queue</div>
    </div>

    <q-card v-if="queue.length === 0">
      <q-card-section>
        <div class="text-subtitle1 text-grey text-center q-py-lg">No pending drafts</div>
      </q-card-section>
    </q-card>

    <div v-else class="q-gutter-y-md">
      <div class="row q-gutter-x-sm">
        <q-btn color="primary" label="Sync All" icon="sync" @click="syncQueue" :loading="isSyncing" class="col" />
        <q-btn flat color="negative" icon="delete" @click="clearQueue" :disable="isSyncing" />
      </div>

      <q-card v-for="item in queue" :key="item.idempotencyKey" bordered flat>
        <q-card-section>
          <div class="text-subtitle2 text-primary text-uppercase">{{ item.method }} {{ item.url }}</div>
          <div class="text-caption text-grey">{{ new Date(item.timestamp).toLocaleString() }}</div>
          <div class="q-mt-sm text-body2">
            <pre class="bg-grey-2 q-pa-sm rounded-borders" style="margin: 0; font-size: 11px; overflow-x: auto;">{{ JSON.stringify(item.payload, null, 2) }}</pre>
          </div>
        </q-card-section>
      </q-card>
    </div>
  </q-page>
</template>

<style scoped>
</style>
