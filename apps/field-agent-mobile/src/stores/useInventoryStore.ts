import { defineStore } from 'pinia'
import api from '@/services/api'
import type { Product, StockItem } from '@/types/domain'

interface CollectionResponse<T> {
  data: T[]
  meta?: unknown
}

export const useInventoryStore = defineStore('inventory', {
  state: () => ({
  }),
  actions: {
    async searchProducts(query: string): Promise<Product[]> {
      try {
        const response = await api.get(`/products?q=${encodeURIComponent(query)}`) as unknown as CollectionResponse<Product>
        return response.data || []
      } catch (err) {
        // Fallback for offline lookup would exist here in Phase 2
        console.warn('Could not fetch products', err)
        return []
      }
    },
    async getInventoryForProduct(productId: string): Promise<StockItem[]> {
      try {
        const response = await api.get(`/inventory?productId=${encodeURIComponent(productId)}`) as unknown as CollectionResponse<StockItem>
        return response.data || []
      } catch (err) {
        console.warn('Could not fetch inventory', err)
        return []
      }
    }
  }
})
