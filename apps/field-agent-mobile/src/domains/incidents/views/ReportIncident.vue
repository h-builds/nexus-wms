<script setup lang="ts">
import { ref } from "vue";
import { useIncidentsStore } from "@/stores/useIncidentsStore";
import { OfflineQueueError } from "@/services/api";
import { useQuasar } from "quasar";

const $q = useQuasar();
const store = useIncidentsStore();

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
      const apiErr = err as { error: { message?: string; details?: any[] } };
      const msg =
        apiErr.error.details?.[0]?.message ||
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
          <q-input
            v-model="incidentDraft.productId"
            label="Product ID *"
            outlined
            hint="Use Product Lookup to find ID"
          />

          <q-input
            v-model="incidentDraft.locationId"
            label="Location ID *"
            outlined
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
