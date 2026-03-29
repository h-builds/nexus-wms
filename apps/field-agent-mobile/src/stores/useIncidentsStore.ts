import { defineStore } from 'pinia'
import api from '@/services/api'
import type { IncidentPayload } from '@/types/domain'
import { syncQueueManager } from '@/offline/SyncQueue'
import type { QueuedRequest } from '@/offline/SyncQueue'

export const useIncidentsStore = defineStore('incidents', {
  state: () => ({
    syncQueue: [] as QueuedRequest[]
  }),
  actions: {
    async loadQueue() {
      this.syncQueue = await syncQueueManager.getQueue()
    },
    async reportIncident(payload: IncidentPayload): Promise<void> {
      const idempotencyKey = crypto.randomUUID()
      try {
        const response = await api.post('/incidents', payload, {
          headers: {
            'Idempotency-Key': idempotencyKey
          }
        })
        await this.loadQueue()
        return response.data
      } catch (err: unknown) {
        await this.loadQueue()
        throw err
      }
    }
  }
})
