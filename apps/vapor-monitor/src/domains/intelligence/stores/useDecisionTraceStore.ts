import { defineStore } from 'pinia';
import { ref } from 'vue';

export type TraceSeverity = 'low' | 'medium' | 'high' | 'critical';
export type TraceStatus = 'advisory' | 'acknowledged' | 'acted_upon' | 'dismissed';

export interface DecisionTraceDto {
    id: string;
    traceType: string;
    agentId: string;
    agentDomain: string;
    detection: string;
    reasoning: string;
    suggestion: string;
    severity: TraceSeverity;
    causationId: string;
    correlationId: string;
    triggerEventIds: string[];
    status: TraceStatus;
    createdAt: string;
}

export interface TraceFilters {
    status?: TraceStatus | null;
    severity?: TraceSeverity | null;
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

interface DecisionTraceApiResponse {
    data: DecisionTraceDto[] | null;
}

interface SingleTraceApiResponse {
    data: DecisionTraceDto | null;
}

interface MetricsApiResponse {
    data: DecisionTraceMetrics | null;
}

const NEW_TRACE_HIGHLIGHT_DURATION_MS = 5000;

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

    function buildTraceQueryParams(): URLSearchParams {
        const queryParams = new URLSearchParams();
        if (activeFilters.value.status) queryParams.append('status', activeFilters.value.status);
        if (activeFilters.value.severity) queryParams.append('severity', activeFilters.value.severity);
        if (activeFilters.value.agentDomain) queryParams.append('agentDomain', activeFilters.value.agentDomain);
        queryParams.append('sort', activeSort.value);
        return queryParams;
    }

    function markNewlyAppearedTraces(fetchedTraces: DecisionTraceDto[]): void {
        if (recentlySeenIds.value.size === 0) return;

        for (const trace of fetchedTraces) {
            if (!recentlySeenIds.value.has(trace.id)) {
                justAppearedIds.value.add(trace.id);
                setTimeout(() => justAppearedIds.value.delete(trace.id), NEW_TRACE_HIGHLIGHT_DURATION_MS);
            }
        }
    }

    async function fetchTraces(filters?: TraceFilters, sort?: string): Promise<void> {
        isLoading.value = true;
        error.value = null;

        if (filters !== undefined) activeFilters.value = filters;
        if (sort !== undefined) activeSort.value = sort;

        try {
            const queryParams = buildTraceQueryParams();
            const response = await fetch(`/api/intelligence/decision-traces?${queryParams.toString()}`, { cache: 'no-store' });

            if (!response.ok) {
                throw new Error(`Decision traces service unavailable (${response.status}).`);
            }

            const json: DecisionTraceApiResponse = await response.json();
            const fetchedTraces: DecisionTraceDto[] = json.data ?? [];

            markNewlyAppearedTraces(fetchedTraces);
            for (const trace of fetchedTraces) {
                recentlySeenIds.value.add(trace.id);
            }
            lastUpdateTimestamp.value = Date.now();
            traces.value = fetchedTraces;
        } catch (fetchError: unknown) {
            const message = fetchError instanceof Error
                ? fetchError.message
                : 'Failed to fetch decision traces.';
            console.error('[DecisionTraceStore] Fetch traces failed:', fetchError);
            error.value = message;
        } finally {
            isLoading.value = false;
        }
    }

    async function fetchMetrics(): Promise<void> {
        try {
            const response = await fetch('/api/intelligence/decision-traces/metrics', { cache: 'no-store' });

            if (!response.ok) {
                throw new Error(`Failed to fetch decision trace metrics (${response.status}).`);
            }

            const json: MetricsApiResponse = await response.json();
            metrics.value = json.data ?? null;
        } catch (fetchError: unknown) {
            const message = fetchError instanceof Error
                ? fetchError.message
                : 'Failed to fetch metrics.';
            console.error('[DecisionTraceStore] Metrics fetch failed:', message);
        }
    }

    async function updateTraceStatus(traceId: string, action: 'acknowledge' | 'act-upon' | 'dismiss', body?: Record<string, string>): Promise<void> {
        try {
            const response = await fetch(`/api/intelligence/decision-traces/${traceId}/${action}`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                ...(body ? { body: JSON.stringify(body) } : {}),
            });

            if (!response.ok) {
                throw new Error(`Failed to ${action} trace ${traceId} (${response.status}).`);
            }

            const json: SingleTraceApiResponse = await response.json();
            if (json.data) {
                const traceIndex = traces.value.findIndex(trace => trace.id === traceId);
                if (traceIndex !== -1) {
                    traces.value[traceIndex] = json.data;
                }
            }

            await fetchMetrics();
        } catch (actionError: unknown) {
            const message = actionError instanceof Error
                ? actionError.message
                : `Failed to ${action} trace.`;
            console.error(`[DecisionTraceStore] ${action} failed:`, actionError);
            error.value = message;
        }
    }

    async function acknowledgeTrace(traceId: string): Promise<void> {
        await updateTraceStatus(traceId, 'acknowledge');
    }

    async function actUponTrace(traceId: string): Promise<void> {
        await updateTraceStatus(traceId, 'act-upon', { actor_id: 'vapor-monitor-user' });
    }

    async function dismissTrace(traceId: string): Promise<void> {
        await updateTraceStatus(traceId, 'dismiss', { actor_id: 'vapor-monitor-user' });
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
        dismissTrace,
    };
});
