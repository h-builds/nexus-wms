<script setup lang="ts">
import { ref } from 'vue'
import { useInventoryStore } from '@/stores/useInventoryStore'
import type { Product, StockItem } from '@/types/domain'

const store = useInventoryStore()

const searchQuery = ref('')
const selectedProduct = ref<Product | null>(null)
const stockItems = ref<StockItem[]>([])
const isLoading = ref(false)

const handleSearch = async () => {
  if (!searchQuery.value) return
  
  isLoading.value = true
  try {
    const products = await store.searchProducts(searchQuery.value)
    if (products.length > 0) {
      selectedProduct.value = products[0]
      stockItems.value = await store.getInventoryForProduct(selectedProduct.value.id)
    } else {
      selectedProduct.value = null
      stockItems.value = []
    }
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <q-page class="q-pa-md">
    <div class="text-h5 q-mb-md">Product & Stock Lookup</div>

    <q-card>
      <q-card-section>
        <q-input 
          v-model="searchQuery" 
          label="Enter SKU or Name" 
          outlined
          clearable
          @keyup.enter="handleSearch"
        >
          <template v-slot:append>
            <q-btn round dense flat icon="search" @click="handleSearch" :loading="isLoading" />
          </template>
        </q-input>
      </q-card-section>
    </q-card>

    <div v-if="selectedProduct" class="q-mt-lg">
      <q-card flat bordered>
        <q-card-section class="bg-primary text-white">
          <div class="text-h6">{{ selectedProduct.name }}</div>
          <div class="text-subtitle2">SKU: {{ selectedProduct.sku }}</div>
        </q-card-section>

        <q-separator />

        <q-card-section>
          <div class="text-subtitle1 q-mb-sm">Available Stock Locations</div>
          
          <q-list bordered separator>
            <q-item v-for="stockItem in stockItems" :key="stockItem.id">
              <q-item-section>
                <q-item-label>Location: {{ stockItem.locationId }}</q-item-label>
                <q-item-label caption>
                  Available: <span class="text-weight-bold text-positive">{{ stockItem.quantityAvailable }}</span>
                  | Blocked: <span class="text-negative">{{ stockItem.quantityBlocked }}</span>
                </q-item-label>
              </q-item-section>
            </q-item>
            
            <q-item v-if="stockItems.length === 0">
              <q-item-section class="text-grey text-center">
                No stock available for this product.
              </q-item-section>
            </q-item>
          </q-list>
        </q-card-section>
      </q-card>
    </div>
  </q-page>
</template>

<style scoped>
</style>
