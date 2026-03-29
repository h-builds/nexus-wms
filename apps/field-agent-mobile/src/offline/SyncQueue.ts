import { get, set } from 'idb-keyval'

export interface QueuedRequest {
  idempotencyKey: string // generated UUID
  method: string
  url: string
  payload: any
  timestamp: string // ISO date
}

export const syncQueueManager = {
  queueKey: 'nexus_wms_sync_queue',

  async getQueue(): Promise<QueuedRequest[]> {
    const queue = await get<QueuedRequest[]>(this.queueKey)
    return queue || []
  },

  async enqueue(request: Omit<QueuedRequest, 'idempotencyKey' | 'timestamp'>) {
    const queue = await this.getQueue()
    const idempotencyKey = crypto.randomUUID()
    const timestamp = new Date().toISOString()
    
    queue.push({
      ...request,
      idempotencyKey,
      timestamp
    })
    
    await set(this.queueKey, queue)
  },

  async dequeue(idempotencyKey: string) {
    const queue = await this.getQueue()
    const newQueue = queue.filter(r => r.idempotencyKey !== idempotencyKey)
    await set(this.queueKey, newQueue)
  },

  async clearQueue() {
    await set(this.queueKey, [])
  }
}
