import type {
  ZoneHeatmapEntry,
  HeatmapIntensity,
  HeatmapConfig,
} from './types';
import { DEFAULT_HEATMAP_CONFIG } from './types';
import type { OccupancySnapshot } from '../occupancy/types';
import type { IncidentsSnapshot } from '../incidents/types';

/**
 * Computes a composite heatmap score per zone from occupancy density
 * and incident count. The score is a weighted blend:
 *   70% occupancy density + 30% incident pressure
 *
 * Incident pressure is normalized: each open incident in a zone adds
 * 20 points (capped at 100). This keeps the scoring deterministic
 * and explainable.
 */
export class HeatmapService {
  private readonly config: HeatmapConfig;

  constructor(config: HeatmapConfig = DEFAULT_HEATMAP_CONFIG) {
    this.config = config;
  }

  compute(
    occupancy: OccupancySnapshot,
    incidents: IncidentsSnapshot,
  ): ZoneHeatmapEntry[] {
    const incidentsByZone = new Map<string, number>();
    for (const zoneIncidentSummary of incidents.zoneIncidents) {
      incidentsByZone.set(zoneIncidentSummary.zoneId, zoneIncidentSummary.totalOpenCount);
    }

    const heatmapEntries: ZoneHeatmapEntry[] = [];

    for (const zone of occupancy.zoneDensities) {
      const occupancyScore = zone.densityPercentage;
      const incidentCount = incidentsByZone.get(zone.zoneId) ?? 0;
      const incidentPressure = Math.min(incidentCount * 20, 100);

      const compositeScore = occupancyScore * 0.7 + incidentPressure * 0.3;

      heatmapEntries.push({
        zoneId: zone.zoneId,
        score: Math.round(compositeScore),
        intensity: this.resolveIntensity(compositeScore),
      });
    }

    return heatmapEntries;
  }

  private resolveIntensity(score: number): HeatmapIntensity {
    if (score >= this.config.highThreshold) return 'high';
    if (score >= this.config.mediumThreshold) return 'medium';
    return 'low';
  }
}
