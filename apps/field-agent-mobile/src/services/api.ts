import axios from 'axios'
import { syncQueueManager } from '../offline/SyncQueue'

export class OfflineQueueError extends Error {
  public readonly isQueuedOffline = true
  constructor(message = 'Device is offline. Action drafted locally.') {
    super(message)
    this.name = 'OfflineQueueError'
  }
}

const api = axios.create({
  baseURL: '/api',
  headers: {
    'Content-Type': 'application/json'
    // Authentication:
    // In MVP phase, backend reads Actor Identity from session cookies or Bearer token (from dev config).
    // We do NOT fake the actor identity here.
  }
})

api.interceptors.response.use(
  (response) => {
    // If we're online and requests succeed, theoretically we'd try to flush queue here
    // but the MVP requirement explicitly defers sync conflict resolution to Phase 2.
    // For now we just return the response.
    return response.data
  },
  async (error) => {
    if (error.response) {
      return Promise.reject(error.response.data)
    }

    const validMethodsForSync = ['post', 'patch', 'put', 'delete']
    const isNetworkError = !navigator.onLine || 
      error.code === 'ERR_NETWORK' || 
      error.message === 'Network Error' || 
      error.message?.includes('Network Error') ||
      !error.response;

    if (validMethodsForSync.includes(error.config?.method?.toLowerCase() || '') && isNetworkError) {
      let parsedPayload = {};
      try {
        if (typeof error.config?.data === 'string') {
          parsedPayload = JSON.parse(error.config.data);
        } else if (error.config?.data && typeof error.config.data === 'object') {
          parsedPayload = error.config.data;
        }
      } catch (parseError) {
        console.warn('[OfflineQueue] Failed to parse request payload for local queueing:', parseError)
      }

      await syncQueueManager.enqueue({
        method: error.config.method || 'post',
        url: error.config.url || '',
        payload: parsedPayload
      })
      
      const offlineErr = new OfflineQueueError()
      return Promise.reject(offlineErr)
    }

    return Promise.reject(error)
  }
)

export default api
