<script setup lang="ts">
import { ref } from 'vue'
import { useEventIngestionStore } from '@/domains/events/stores/useEventIngestionStore'
import { useEventStateStore } from '@/domains/events/stores/useEventStateStore'

const ingestionStore = useEventIngestionStore()
const stateStore = useEventStateStore()
const isPanelOpen = ref(false)
const activeTab = ref<'ingestion' | 'interpreted'>('ingestion')

function toggleDebuggerPanel() {
  isPanelOpen.value = !isPanelOpen.value
  
  if (isPanelOpen.value && !ingestionStore.isListening) {
    ingestionStore.startListening()
  }
}

function clearIngestionLog() {
  ingestionStore.clearLog()
}

// Auto-start listener on mount for Phase requirement
ingestionStore.startListening()
// Initiate state store binding to rawEvents
stateStore.$id; 
</script>

<template>
  <div class="fixed bottom-4 right-4 z-50 font-mono text-xs shadow-2xl">
    <button 
      @click="toggleDebuggerPanel"
      class="bg-zinc-800 text-zinc-100 px-4 py-2 rounded-t-xl hover:bg-zinc-700 transition flex items-center gap-2 border border-zinc-700 w-full"
    >
      <div 
        class="w-2 h-2 rounded-full" 
        :class="ingestionStore.isListening ? 'bg-green-500 shadow-[0_0_8px_rgba(34,197,94,0.6)]' : 'bg-red-500'"
      ></div>
      Event Ingestion Log ({{ ingestionStore.rawEvents.length }})
    </button>

    <div 
      v-if="isPanelOpen" 
      class="bg-zinc-900 border border-zinc-700 w-[500px] h-[400px] shadow-lg flex flex-col rounded-b-xl rounded-tl-xl overflow-hidden"
    >
      <div class="flex justify-between items-center bg-zinc-800 border-b border-zinc-700">
        <div class="flex">
          <button 
            @click="activeTab = 'ingestion'" 
            :class="['px-4 py-2 text-[11px] font-bold uppercase tracking-wider', activeTab === 'ingestion' ? 'bg-zinc-700 text-white' : 'text-zinc-500 hover:text-zinc-300']"
          >
            Raw Stream
          </button>
          <button 
            @click="activeTab = 'interpreted'" 
            :class="['px-4 py-2 text-[11px] font-bold uppercase tracking-wider', activeTab === 'interpreted' ? 'bg-blue-900 text-blue-200' : 'text-zinc-500 hover:text-zinc-300']"
          >
            Interpreted State
          </button>
        </div>
        <button @click="clearIngestionLog" class="text-zinc-500 hover:text-white transition px-4 py-2">Clear</button>
      </div>
      
      <div v-if="activeTab === 'ingestion'" class="flex-1 overflow-y-auto p-4 space-y-3 bg-zinc-950/50">
        <div v-if="ingestionStore.rawEvents.length === 0" class="text-zinc-600 italic text-center mt-10">
          Listening for Canonical Events...
        </div>

        <div 
          v-for="event in ingestionStore.rawEvents" 
          :key="event.eventId"
          class="bg-black border border-zinc-800 p-3 rounded text-[11px] leading-relaxed relative overflow-hidden"
        >
          <div class="absolute left-0 top-0 bottom-0 w-1 bg-blue-500/50"></div>
          
          <div class="flex justify-between items-baseline mb-2">
            <div class="text-blue-400 font-bold ml-1">{{ event.eventType }}</div>
            <div class="text-zinc-500">{{ new Date(event.occurredAt).toLocaleTimeString() }}</div>
          </div>
          
          <div class="grid grid-cols-[100px_1fr] gap-x-2 gap-y-1 mb-2">
            <span class="text-zinc-500">Event ID</span>
            <span class="text-zinc-300 truncate" :title="event.eventId">{{ event.eventId }}</span>
            
            <span class="text-zinc-500">Correlation ID</span>
            <span class="text-amber-500/80 truncate" :title="event.correlationId">{{ event.correlationId }}</span>
            
            <span class="text-zinc-500">Causation ID</span>
            <span class="text-amber-500/50 truncate" :title="event.causationId">{{ event.causationId }}</span>

            <span class="text-zinc-500">Actor</span>
            <span class="text-zinc-400">{{ event.actorId }}</span>
          </div>

          <details class="mt-2 text-zinc-400">
            <summary class="cursor-pointer hover:text-white">Payload (v{{ event.eventVersion }})</summary>
            <pre class="mt-1 p-2 bg-zinc-900 rounded overflow-x-auto text-[10px]">{{ JSON.stringify(event.payload, null, 2) }}</pre>
          </details>
        </div>
      </div>

      <div v-else class="flex-1 overflow-y-auto p-4 bg-zinc-950/50">
         <div class="text-xs text-blue-400 font-bold mb-2 tracking-wider">DETERMINISTIC PLAYBACK STATE</div>
         <p class="text-[10px] text-zinc-500 mb-4">This represents objective truth mathematically derived from the raw event stream. Both applications should hash to the exact same struct.</p>
         
         <div class="grid grid-cols-2 gap-4 mb-4">
            <div class="bg-black p-3 border border-zinc-800 rounded">
                <div class="text-zinc-600 mb-1">Last Timestamp</div>
                <div class="text-green-400 font-medium">{{ stateStore.debuggerState.timestamp || 'awaiting event...' }}</div>
            </div>
            <div class="bg-black p-3 border border-zinc-800 rounded">
                <div class="text-zinc-600 mb-1">Active Quantities</div>
                <div class="text-zinc-200 font-medium">{{ stateStore.debuggerState.inventoryKeys }} locs</div>
            </div>
             <div class="bg-black p-3 border border-zinc-800 rounded">
                <div class="text-zinc-600 mb-1">Open Incidents</div>
                <div class="text-red-400 font-medium">{{ stateStore.debuggerState.openIncidentsCount }}</div>
            </div>
             <div class="bg-black p-3 border border-zinc-800 rounded">
                <div class="text-zinc-600 mb-1">Processed Movements</div>
                <div class="text-amber-400 font-medium">{{ stateStore.debuggerState.totalMovementsProcessed }}</div>
            </div>
         </div>

         <div class="text-[10px] text-zinc-500 mb-2 uppercase tracking-widest font-bold">Absolute Structure</div>
         <pre class="bg-black p-3 border border-zinc-800 rounded overflow-x-auto text-[10px] text-zinc-400">{{ JSON.stringify(stateStore._rawStateRef, null, 2) }}</pre>
      </div>
    </div>
  </div>
</template>
