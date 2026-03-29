<script setup lang="ts">
import { ref } from "vue";
import { useMovementsStore } from "@/stores/useMovementsStore";
import { OfflineQueueError } from "@/services/api";
import { useQuasar } from "quasar";

const $q = useQuasar();
const store = useMovementsStore();

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
      const apiErr = err as { error: { message?: string; details?: any[] } };
      const msg =
        apiErr.error.details?.[0]?.message ||
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

          <q-input
            v-model="movementDraft.productId"
            label="Product ID *"
            outlined
          />

          <q-input
            v-model="movementDraft.fromLocationId"
            label="Source Location ID *"
            outlined
            v-if="
              movementDraft.type === 'relocation' ||
              movementDraft.type === 'adjustment'
            "
          />
          <q-input
            v-model="movementDraft.toLocationId"
            label="Destination Location ID *"
            outlined
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
