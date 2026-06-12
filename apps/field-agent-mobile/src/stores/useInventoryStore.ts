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
        const msg = err instanceof Error ? err.message : 'Unknown network error';
        console.error('[InventoryStore] Product search failed:', msg);
        throw new Error(`Failed to search products: ${msg}`);
      }
    },
    async getInventoryForProduct(productId: string): Promise<StockItem[]> {
      try {
        const response = await api.get(`/inventory?productId=${encodeURIComponent(productId)}`) as unknown as CollectionResponse<StockItem>
        return response.data || []
      } catch (err) {
        const msg = err instanceof Error ? err.message : 'Unknown network error';
        console.error('[InventoryStore] Inventory lookup failed:', msg);
        throw new Error(`Failed to fetch inventory: ${msg}`);
      }
    }
  }
})
