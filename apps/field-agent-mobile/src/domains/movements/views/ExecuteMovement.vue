<script setup lang="ts">
import { ref } from "vue";
import { useMovementsStore } from "@/stores/useMovementsStore";
import { useInventoryStore } from "@/stores/useInventoryStore";
import { useLocationStore } from "@/stores/useLocationStore";
import { OfflineQueueError } from "@/services/api";
import { useQuasar } from "quasar";

const $q = useQuasar();
const store = useMovementsStore();
const inventoryStore = useInventoryStore();
const locationStore = useLocationStore();

const movementDraft = ref({
  type: "receipt",
  productId: "",
  fromLocationId: "",
  toLocationId: "",
  quantity: 1,
  reference: "",
});

const isSubmitting = ref(false);

const movementTypes = [
  { label: "Inbound Receipt", value: "receipt" },
  { label: "Location Transfer", value: "relocation" },
  { label: "Positive Adjustment", value: "adjustment" },
];

const productOptions = ref<{label: string, value: string}[]>([]);
const filterProducts = async (val: string, update: (callback: () => void) => void) => {
  if (val === '') {
    update(() => {
      productOptions.value = []
    })
    return
  }
  const products = await inventoryStore.searchProducts(val)
  update(() => {
    productOptions.value = products.map((p) => ({
      label: `${p.name} (${p.sku})`,
      value: p.id
    }))
  })
}

const locationOptions = ref<{label: string, value: string}[]>([]);
const filterLocations = async (val: string, update: (callback: () => void) => void) => {
  if (val === '') {
    update(() => {
      locationOptions.value = []
    })
    return
  }
  const locations = await locationStore.searchLocations(val)
  update(() => {
    locationOptions.value = locations.map((l) => ({
      label: `${l.label} (${l.id})`,
      value: l.id
    }))
  })
}

const submitMovement = async () => {
  if (!movementDraft.value.productId || !movementDraft.value.quantity) {
    $q.notify({ type: "warning", message: "Missing required fields" });
    return;
  }

  isSubmitting.value = true;
  try {
    await store.registerMovement({ ...movementDraft.value });
    $q.notify({
      type: "positive",
      message: "Movement registered successfully",
    });
    movementDraft.value = {
      type: "receipt",
      productId: "",
      fromLocationId: "",
      toLocationId: "",
      quantity: 1,
      reference: "",
    };
  } catch (err: unknown) {
    const isOfflineErr =
      err instanceof OfflineQueueError ||
      (typeof err === "object" &&
        err !== null &&
        "isQueuedOffline" in err &&
        (err as { isQueuedOffline: boolean }).isQueuedOffline);

    if (isOfflineErr) {
      const offlineMsg =
        (err as { message?: string }).message ||
        "Offline - Action Queued as Draft";
      $q.notify({ type: "warning", message: offlineMsg });
      movementDraft.value = {
        type: "receipt",
        productId: "",
        fromLocationId: "",
        toLocationId: "",
        quantity: 1,
        reference: "",
      };
    } else if (err && typeof err === "object" && "error" in err) {
      const apiErr = err as { error: { message?: string; details?: Record<string, unknown>[] } };
      const msg =
        (apiErr.error.details?.[0]?.message as string) ||
        apiErr.error.message ||
        "Validation failed";
      $q.notify({ type: "negative", message: msg });
    } else if (err instanceof Error) {
      $q.notify({
        type: "negative",
        message: err.message || "Failed to register movement",
      });
    } else {
      $q.notify({ type: "negative", message: "An unknown error occurred" });
    }
  } finally {
    isSubmitting.value = false;
  }
};
</script>

<template>
  <q-page class="q-pa-md">
    <div class="text-h5 q-mb-md">Register Movement</div>

    <q-card>
      <q-card-section>
        <q-form @submit="submitMovement" class="q-gutter-md">
          <q-select
            v-model="movementDraft.type"
            :options="movementTypes"
            emit-value
            map-options
            label="Movement Type *"
            outlined
          />

          <q-select
            v-model="movementDraft.productId"
            :options="productOptions"
            use-input
            fill-input
            hide-selected
            emit-value
            map-options
            @filter="filterProducts"
            label="Product *"
            outlined
            hint="Type to search products"
          />

          <q-select
            v-model="movementDraft.fromLocationId"
            :options="locationOptions"
            use-input
            fill-input
            hide-selected
            emit-value
            map-options
            @filter="filterLocations"
            label="Source Location *"
            outlined
            hint="Type to search source locations"
            v-if="
              movementDraft.type === 'relocation' ||
              movementDraft.type === 'adjustment'
            "
          />

          <q-select
            v-model="movementDraft.toLocationId"
            :options="locationOptions"
            use-input
            fill-input
            hide-selected
            emit-value
            map-options
            @filter="filterLocations"
            label="Destination Location *"
            outlined
            hint="Type to search destination locations"
            v-if="
              movementDraft.type === 'relocation' ||
              movementDraft.type === 'receipt' ||
              movementDraft.type === 'adjustment'
            "
          />

          <q-input
            v-model.number="movementDraft.quantity"
            type="number"
            label="Quantity *"
            outlined
            min="1"
          />

          <q-input
            v-model="movementDraft.reference"
            label="Reason / Reference"
            outlined
          />

          <div class="q-mt-md">
            <q-btn
              label="Execute"
              type="submit"
              color="secondary"
              class="full-width"
              :loading="isSubmitting"
            />
          </div>
        </q-form>
      </q-card-section>
    </q-card>
  </q-page>
</template>

<style scoped></style>
