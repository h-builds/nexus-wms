<script setup lang="ts">
import { ref } from "vue";
import { useIncidentsStore } from "@/stores/useIncidentsStore";
import { useInventoryStore } from "@/stores/useInventoryStore";
import { useLocationStore } from "@/stores/useLocationStore";
import { OfflineQueueError } from "@/services/api";
import { useQuasar } from "quasar";

const $q = useQuasar();
const store = useIncidentsStore();
const inventoryStore = useInventoryStore();
const locationStore = useLocationStore();

const incidentDraft = ref({
  productId: "",
  locationId: "",
  type: "damage",
  severity: "low",
  description: "",
  quantityAffected: 1,
});

const isSubmitting = ref(false);

const typeOptions = [
  { label: "Damage", value: "damage" },
  { label: "Missing", value: "missing" },
  { label: "Safety", value: "safety" },
  { label: "Other", value: "other" },
];

const severityOptions = [
  { label: "Low", value: "low" },
  { label: "Medium", value: "medium" },
  { label: "High", value: "high" },
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

const submitIncident = async () => {
  if (
    !incidentDraft.value.productId ||
    !incidentDraft.value.locationId ||
    !incidentDraft.value.description
  ) {
    $q.notify({ type: "warning", message: "Please fill all required fields" });
    return;
  }

  isSubmitting.value = true;
  try {
    await store.reportIncident({ ...incidentDraft.value });
    $q.notify({ type: "positive", message: "Incident reported successfully" });
    incidentDraft.value = {
      productId: "",
      locationId: "",
      type: "damage",
      severity: "low",
      description: "",
      quantityAffected: 1,
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
      incidentDraft.value = {
        productId: "",
        locationId: "",
        type: "damage",
        severity: "low",
        description: "",
        quantityAffected: 1,
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
        message: err.message || "Failed to report incident",
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
    <div class="text-h5 q-mb-md">Report Incident</div>

    <q-card>
      <q-card-section>
        <q-form @submit="submitIncident" class="q-gutter-md">
          <q-select
            v-model="incidentDraft.productId"
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
            v-model="incidentDraft.locationId"
            :options="locationOptions"
            use-input
            fill-input
            hide-selected
            emit-value
            map-options
            @filter="filterLocations"
            label="Location *"
            outlined
            hint="Type to search locations"
          />

          <q-select
            v-model="incidentDraft.type"
            :options="typeOptions"
            emit-value
            map-options
            label="Incident Type"
            outlined
          />

          <q-select
            v-model="incidentDraft.severity"
            :options="severityOptions"
            emit-value
            map-options
            label="Severity"
            outlined
          />

          <q-input
            v-model.number="incidentDraft.quantityAffected"
            type="number"
            label="Quantity Affected"
            outlined
            min="1"
          />

          <q-input
            v-model="incidentDraft.description"
            type="textarea"
            label="Description *"
            outlined
            placeholder="Describe the anomaly clearly"
          />

          <div class="q-mt-md">
            <q-btn
              label="Submit Report"
              type="submit"
              color="warning"
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
