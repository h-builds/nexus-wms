import { defineStore } from 'pinia';
import { ref } from 'vue';
import { fetchFromApi, API_BASE_URL } from '@/domains/shared/api';

export interface DecisionTraceDto {
    id: string;
    traceType: string;
    agentId: string;
    agentDomain: string;
    detection: string;
    reasoning: string;
    suggestion: string;
    severity: string;
    causationId: string;
    correlationId: string;
    triggerEventIds: string[];
    status: string;
    createdAt: string;
}

export interface TraceFilters {
    status?: string | null;
    severity?: string | null;
    agentDomain?: string | null;
}

export interface DecisionTraceMetrics {
    totalTraces: number;
    advisoryCount: number;
    acknowledgedCount: number;
    actedUponCount: number;
    dismissedCount: number;
    criticalCount: number;
    highCount: number;
    mediumCount: number;
    lowCount: number;
    inventoryDomainCount?: number;
    incidentsDomainCount?: number;
    movementsDomainCount?: number;
    monitoringDomainCount?: number;
}

export const useDecisionTraceStore = defineStore('decisionTrace', () => {
    const traces = ref<DecisionTraceDto[]>([]);
    const metrics = ref<DecisionTraceMetrics | null>(null);
    const isLoading = ref<boolean>(false);
    const error = ref<string | null>(null);
    const activeFilters = ref<TraceFilters>({});
    const activeSort = ref<string>('createdAt_desc');
    const recentlySeenIds = ref(new Set<string>());
    const justAppearedIds = ref(new Set<string>());
    const lastUpdateTimestamp = ref<number | null>(null);

    async function fetchTraces(filters?: TraceFilters, sort?: string): Promise<void> {
        isLoading.value = true;
        error.value = null;

        if (filters !== undefined) activeFilters.value = filters;
        if (sort !== undefined) activeSort.value = sort;

        try {
            const queryParams = new URLSearchParams();
            if (activeFilters.value.status) queryParams.append('status', activeFilters.value.status);
            if (activeFilters.value.severity) queryParams.append('severity', activeFilters.value.severity);
            if (activeFilters.value.agentDomain) queryParams.append('agentDomain', activeFilters.value.agentDomain);
            queryParams.append('sort', activeSort.value);

            const response = await fetchFromApi<DecisionTraceDto[]>(`/intelligence/decision-traces?${queryParams.toString()}`);
            const fetchedTraces: DecisionTraceDto[] = response.data || [];

            if (recentlySeenIds.value.size > 0) {
                fetchedTraces.forEach(t => {
                    if (!recentlySeenIds.value.has(t.id)) {
                        justAppearedIds.value.add(t.id);
                        setTimeout(() => justAppearedIds.value.delete(t.id), 5000);
                    }
                });
            }

            fetchedTraces.forEach(t => recentlySeenIds.value.add(t.id));
            lastUpdateTimestamp.value = Date.now();

            traces.value = fetchedTraces;
        } catch (fetchError: unknown) {
            console.error(fetchError);
            if (fetchError instanceof Error) {
                error.value = fetchError.message;
            } else {
                error.value = "Failed to fetch decision traces.";
            }
        } finally {
            isLoading.value = false;
        }
    }

    async function fetchMetrics(): Promise<void> {
        try {
            const response = await fetchFromApi<DecisionTraceMetrics>(`/intelligence/decision-traces/metrics`);
            metrics.value = response.data || null;
        } catch (fetchError: unknown) {
            console.error('Metrics fetch error:', fetchError);
            error.value = fetchError instanceof Error ? fetchError.message : 'Failed to fetch decision trace metrics.';
        }
    }

    async function acknowledgeTrace(id: string): Promise<void> {
        try {
            error.value = null;
            const url = new URL(`/api/intelligence/decision-traces/${id}/acknowledge`, API_BASE_URL).toString();
            const response = await fetch(url, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }
            });
            if (!response.ok) throw new Error(`Failed to acknowledge trace: HTTP ${response.status}`);
            const json = (await response.json()) as { data?: DecisionTraceDto };
            const index = traces.value.findIndex(t => t.id === id);
            if (index !== -1 && json.data) {
                traces.value[index] = json.data;
            }
            await fetchMetrics();
        } catch (e: unknown) {
            console.error('Acknowledge trace error:', e);
            error.value = e instanceof Error ? e.message : 'An unexpected error occurred while acknowledging the trace.';
        }
    }

    async function actUponTrace(id: string): Promise<void> {
        try {
            error.value = null;
            const url = new URL(`/api/intelligence/decision-traces/${id}/act-upon`, API_BASE_URL).toString();
            const response = await fetch(url, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ actor_id: 'orchestrator-twin-user' })
            });
            if (!response.ok) throw new Error(`Failed to act upon trace: HTTP ${response.status}`);
            const json = (await response.json()) as { data?: DecisionTraceDto };
            const index = traces.value.findIndex(t => t.id === id);
            if (index !== -1 && json.data) {
                traces.value[index] = json.data;
            }
            await fetchMetrics();
        } catch (e: unknown) {
            console.error('Act upon trace error:', e);
            error.value = e instanceof Error ? e.message : 'An unexpected error occurred while acting upon the trace.';
        }
    }

    async function dismissTrace(id: string): Promise<void> {
        try {
            error.value = null;
            const url = new URL(`/api/intelligence/decision-traces/${id}/dismiss`, API_BASE_URL).toString();
            const response = await fetch(url, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ actor_id: 'orchestrator-twin-user' })
            });
            if (!response.ok) throw new Error(`Failed to dismiss trace: HTTP ${response.status}`);
            const json = (await response.json()) as { data?: DecisionTraceDto };
            const index = traces.value.findIndex(t => t.id === id);
            if (index !== -1 && json.data) {
                traces.value[index] = json.data;
            }
            await fetchMetrics();
        } catch (e: unknown) {
            console.error('Dismiss trace error:', e);
            error.value = e instanceof Error ? e.message : 'An unexpected error occurred while dismissing the trace.';
        }
    }

    return {
        traces,
        isLoading,
        error,
        activeFilters,
        activeSort,
        metrics,
        recentlySeenIds,
        justAppearedIds,
        lastUpdateTimestamp,
        fetchTraces,
        fetchMetrics,
        acknowledgeTrace,
        actUponTrace,
        dismissTrace
    };
});
