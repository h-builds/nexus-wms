import { defineStore } from 'pinia'
import api from '@/services/api'
import type { Location } from '@/types/domain'

interface CollectionResponse<T> {
  data: T[]
  meta?: unknown
}

export const useLocationStore = defineStore('location', {
  state: () => ({
  }),
  actions: {
    async searchLocations(query: string): Promise<Location[]> {
      try {
        const response = await api.get(`/locations?q=${encodeURIComponent(query)}`) as unknown as CollectionResponse<Location>
        return response.data || []
      } catch (err) {
        const msg = err instanceof Error ? err.message : 'Unknown network error';
        console.error('[LocationStore] Search failed:', msg);
        throw new Error(`Failed to search locations: ${msg}`);
      }
    }
  }
})
