import { defineStore } from 'pinia'
import api from '@/services/api'
import type { MovementPayload } from '@/types/domain'
import { syncQueueManager } from '@/offline/SyncQueue'
import type { QueuedRequest } from '@/offline/SyncQueue'

export const useMovementsStore = defineStore('movements', {
  state: () => ({
    syncQueue: [] as QueuedRequest[]
  }),
  actions: {
    async loadQueue() {
      this.syncQueue = await syncQueueManager.getQueue()
    },
    async registerMovement(payload: MovementPayload): Promise<void> {
      const idempotencyKey = crypto.randomUUID()
      try {
        const response = await api.post('/movements', payload, {
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
