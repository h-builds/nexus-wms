<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'

const isOffline = ref(!navigator.onLine)

const updateOnlineStatus = () => {
  isOffline.value = !navigator.onLine
}

onMounted(() => {
  window.addEventListener('online', updateOnlineStatus)
  window.addEventListener('offline', updateOnlineStatus)
})

onUnmounted(() => {
  window.removeEventListener('online', updateOnlineStatus)
  window.removeEventListener('offline', updateOnlineStatus)
})
</script>

<template>
  <q-layout view="hHh lpR fFf">
    <q-header elevated class="bg-primary text-white">
      <q-toolbar>
        <q-btn dense flat round icon="menu" />
        <q-toolbar-title>
          NexusWMS Field Agent
        </q-toolbar-title>
      </q-toolbar>
      <div v-if="isOffline" class="bg-warning text-dark text-center q-pa-xs">
        <q-icon name="cloud_off" /> Offline Mode - Actions will be queued
      </div>
    </q-header>

    <q-drawer show-if-above side="left" bordered>
      <q-list>
        <q-item clickable v-ripple to="/">
          <q-item-section avatar>
            <q-icon name="home" />
          </q-item-section>
          <q-item-section>Field Home</q-item-section>
        </q-item>
        <q-item clickable v-ripple to="/product-lookup">
          <q-item-section avatar>
            <q-icon name="search" />
          </q-item-section>
          <q-item-section>Product & Stock Lookup</q-item-section>
        </q-item>
        <q-item clickable v-ripple to="/incidents/report">
          <q-item-section avatar>
            <q-icon name="warning" />
          </q-item-section>
          <q-item-section>Report Incident</q-item-section>
        </q-item>
        <q-item clickable v-ripple to="/movements/register">
          <q-item-section avatar>
            <q-icon name="swap_horiz" />
          </q-item-section>
          <q-item-section>Register Movement</q-item-section>
        </q-item>
      </q-list>
    </q-drawer>

    <q-page-container>
      <router-view />
    </q-page-container>

  </q-layout>
</template>

<style scoped>
/* App specific overrides using Vanilla CSS */
</style>
