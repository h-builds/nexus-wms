import type {
  Recommendation,
  RecommendationConfig,
  RecommendationPriority,
} from './types';
import { DEFAULT_RECOMMENDATION_CONFIG } from './types';
import type { OccupancySnapshot, ZoneDensity } from '../occupancy/types';
import type { IncidentsSnapshot, ZoneIncidents } from '../incidents/types';
import type { LayoutSnapshot } from '../layout/types';
import type { SimulationResult } from '../simulation/types';

/**
 * Deterministic, rule-based recommendation engine.
 *
 * Each rule maps a warehouse condition to an explicit directive:
 *   condition → factual state (what triggered it)
 *   risk      → operational consequence (what will go wrong)
 *   action    → specific next step (what the operator must do)
 *
 * Priority assignment:
 *   immediate — active system constraint preventing operations
 *   next      — near-future risk requiring action within the shift
 *   monitor   — informational signal, no immediate action required
 *
 * Target zone selection:
 *   When redistribution is recommended, the engine identifies the
 *   lowest-occupancy zone from the current snapshot. If that zone
 *   is the same as the source zone, no target is suggested.
 *   No optimization algorithm is used — simple min-density comparison.
 *
 * Rules:
 *   R1 — High occupancy warning (≥80%)       → priority: next
 *   R2 — Critical occupancy (≥95%)           → priority: immediate
 *   R3 — Zone imbalance (gap ≥40%)           → priority: next | monitor
 *   R4 — Incident cluster (≥2 open)          → priority: next | immediate
 *   R5 — Incident escalation (high+dense)    → priority: immediate
 *   R6 — Blocked locations                   → priority: monitor | next
 *   R7 — Simulation overflow (unplaced)      → priority: immediate
 *   R8 — Simulation zone impact (→ critical) → priority: next
 */
export class RecommendationService {
  private readonly config: RecommendationConfig;

  constructor(config: RecommendationConfig = DEFAULT_RECOMMENDATION_CONFIG) {
    this.config = config;
  }

  /**
   * Evaluates all rules against the current warehouse state.
   * Returns recommendations sorted by priority (immediate first), then severity.
   */
  evaluate(
    layout: LayoutSnapshot,
    occupancy: OccupancySnapshot,
    incidents: IncidentsSnapshot,
    simulation?: SimulationResult | null,
  ): Recommendation[] {
    const recommendations: Recommendation[] = [];

    const densityMap = new Map<string, ZoneDensity>();
    for (const zone of occupancy.zoneDensities) {
      densityMap.set(zone.zoneId, zone);
    }

    recommendations.push(...this.ruleHighOccupancy(occupancy, densityMap));
    recommendations.push(...this.ruleZoneImbalance(occupancy));
    recommendations.push(...this.ruleIncidentCluster(incidents));
    recommendations.push(...this.ruleIncidentEscalation(incidents, densityMap));
    recommendations.push(...this.ruleBlockedLocations(layout, densityMap));

    if (simulation) {
      recommendations.push(...this.ruleSimulationOverflow(simulation, densityMap));
      recommendations.push(...this.ruleSimulationZoneImpact(simulation, occupancy));
    }

    return this.sortByPriorityThenSeverity(recommendations);
  }

  // ─── R1 & R2: High Occupancy ───────────────────────────────────────

  private ruleHighOccupancy(
    occupancy: OccupancySnapshot,
    _densityMap: Map<string, ZoneDensity>,
  ): Recommendation[] {
    const zoneRecommendations: Recommendation[] = [];
    const targetZone = this.findLowestDensityZone(occupancy.zoneDensities);

    for (const zone of occupancy.zoneDensities) {
      const densityPercentageRounded = Math.round(zone.densityPercentage);

      if (zone.densityPercentage >= this.config.highOccupancyCriticalPct) {
        // R2: Critical — active constraint
        zoneRecommendations.push({
          category: 'redistribution',
          severity: 'critical',
          priority: 'immediate',
          zoneId: zone.zoneId,
          condition: `Zone ${zone.zoneId} is at ${densityPercentageRounded}% occupancy (${zone.occupiedBins}/${zone.totalBins} bins full).`,
          risk: 'Inbound allocations will fail. New stock cannot be placed, causing receiving dock backup.',
          action: this.buildRedistributionAction(zone.zoneId, targetZone),
        });
      } else if (zone.densityPercentage >= this.config.highOccupancyWarningPct) {
        // R1: Warning — approaching constraint
        zoneRecommendations.push({
          category: 'redistribution',
          severity: 'warning',
          priority: 'next',
          zoneId: zone.zoneId,
          condition: `Zone ${zone.zoneId} is at ${densityPercentageRounded}% occupancy (${zone.occupiedBins}/${zone.totalBins} bins full).`,
          risk: `Zone is approaching capacity. ${100 - densityPercentageRounded}% remaining before allocations fail.`,
          action: this.buildRedistributionAction(zone.zoneId, targetZone, 'Pre-emptively relocate slow-moving stock from'),
        });
      }
    }

    return zoneRecommendations;
  }

  // ─── R3: Zone Imbalance ────────────────────────────────────────────

  private ruleZoneImbalance(occupancy: OccupancySnapshot): Recommendation[] {
    if (occupancy.zoneDensities.length < 2) return [];

    const sortedDensities = [...occupancy.zoneDensities].sort(
      (a, b) => b.densityPercentage - a.densityPercentage,
    );

    const highestDensityZone = sortedDensities[0];
    const lowestDensityZone = sortedDensities[sortedDensities.length - 1];
    const densityGap = highestDensityZone.densityPercentage - lowestDensityZone.densityPercentage;

    if (densityGap < this.config.imbalanceGapPct) return [];

    const highPct = Math.round(highestDensityZone.densityPercentage);
    const lowPct = Math.round(lowestDensityZone.densityPercentage);
    const availableSlots = lowestDensityZone.totalBins - lowestDensityZone.occupiedBins;
    const priority: RecommendationPriority = densityGap >= 60 ? 'next' : 'monitor';

    return [{
      category: 'imbalance',
      severity: densityGap >= 60 ? 'warning' : 'info',
      priority,
      zoneId: highestDensityZone.zoneId,
      condition: `Zone ${highestDensityZone.zoneId} is at ${highPct}% while Zone ${lowestDensityZone.zoneId} is at ${lowPct}%. Gap: ${Math.round(densityGap)}pp.`,
      risk: `Uneven load creates congestion in Zone ${highestDensityZone.zoneId} and wastes ${availableSlots} available slot(s) in Zone ${lowestDensityZone.zoneId}.`,
      action: `Move stock from Zone ${highestDensityZone.zoneId} to Zone ${lowestDensityZone.zoneId} (${lowPct}% occupied, ${availableSlots} slots available).`,
    }];
  }

  // ─── R4: Incident Cluster ─────────────────────────────────────────

  private ruleIncidentCluster(incidents: IncidentsSnapshot): Recommendation[] {
    const zoneRecommendations: Recommendation[] = [];

    for (const zone of incidents.zoneIncidents) {
      if (zone.totalOpenCount < this.config.incidentClusterThreshold) continue;

      const hasCritical = zone.criticalCount > 0;
      zoneRecommendations.push({
        category: 'inspection',
        severity: hasCritical ? 'critical' : 'warning',
        priority: hasCritical ? 'immediate' : 'next',
        zoneId: zone.zoneId,
        condition: `Zone ${zone.zoneId} has ${zone.totalOpenCount} open incident(s)${hasCritical ? `, including ${zone.criticalCount} critical` : ''}.`,
        risk: 'Clustered incidents indicate a systemic issue — equipment damage, environmental hazard, or process failure.',
        action: `Dispatch inspection team to Zone ${zone.zoneId}. Quarantine affected bins until root cause is identified.`,
      });
    }

    return zoneRecommendations;
  }

  // ─── R5: Incident Escalation ───────────────────────────────────────

  private ruleIncidentEscalation(
    incidents: IncidentsSnapshot,
    densityMap: Map<string, ZoneDensity>,
  ): Recommendation[] {
    const zoneRecommendations: Recommendation[] = [];

    for (const zone of incidents.zoneIncidents) {
      if (zone.criticalCount === 0) continue;

      const density = densityMap.get(zone.zoneId);
      if (!density || density.densityPercentage < this.config.highOccupancyWarningPct) continue;

      const densityPercentageRounded = Math.round(density.densityPercentage);
      zoneRecommendations.push({
        category: 'escalation',
        severity: 'critical',
        priority: 'immediate',
        zoneId: zone.zoneId,
        condition: `Critical incident active in Zone ${zone.zoneId} (${densityPercentageRounded}% occupied, ${density.occupiedBins} bins at risk).`,
        risk: 'High-severity incident in a dense zone amplifies exposure — more stock faces damage, contamination, or access failure.',
        action: `Prioritize incident resolution in Zone ${zone.zoneId}. Re-route all inbound to other zones until resolved.`,
      });
    }

    return zoneRecommendations;
  }

  // ─── R6: Blocked Locations ─────────────────────────────────────────

  private ruleBlockedLocations(
    layout: LayoutSnapshot,
    densityMap: Map<string, ZoneDensity>,
  ): Recommendation[] {
    const zoneRecommendations: Recommendation[] = [];

    for (const warehouse of layout.warehouses) {
      for (const zone of warehouse.zones) {
        let blockedCount = 0;
        let totalBins = 0;

        for (const aisle of zone.aisles) {
          for (const rack of aisle.racks) {
            totalBins += rack.bins.length;
            for (const bin of rack.bins) {
              if (bin.isBlocked) blockedCount++;
            }
          }
        }

        if (blockedCount === 0) continue;

        const density = densityMap.get(zone.id);
        const isHighDensity = density && density.densityPercentage >= this.config.highOccupancyWarningPct;
        const severity = blockedCount >= 3 || isHighDensity ? 'warning' : 'info';
        const priority: RecommendationPriority = isHighDensity ? 'next' : 'monitor';

        const densityNote = density ? ` Zone is at ${Math.round(density.densityPercentage)}% occupancy.` : '';

        zoneRecommendations.push({
          category: 'reallocation',
          severity,
          priority,
          zoneId: zone.id,
          condition: `Zone ${zone.id} has ${blockedCount} blocked location(s) out of ${totalBins} total.${densityNote}`,
          risk: `Blocked bins reduce effective capacity by ${blockedCount} slot(s). Zone may reach premature capacity limits.`,
          action: `Investigate and clear blocked locations in Zone ${zone.id}. Route inbound to alternative zones until resolved.`,
        });
      }
    }

    return zoneRecommendations;
  }

  // ─── R7: Simulation Overflow ───────────────────────────────────────

  private ruleSimulationOverflow(
    simulation: SimulationResult,
    densityMap: Map<string, ZoneDensity>,
  ): Recommendation[] {
    if (simulation.totalUnitsUnplaced === 0) return [];

    const totalRequested = simulation.totalUnitsAllocated + simulation.totalUnitsUnplaced;
    const allZones = [...densityMap.values()];
    const availableSlots = allZones.reduce(
      (totalAvailable, zoneDensity) => totalAvailable + (zoneDensity.totalBins - zoneDensity.occupiedBins),
      0,
    );

    // Build a summary of full zones for consistency with simulation panel
    const fullZones = allZones
      .filter(zoneDensity => zoneDensity.densityPercentage >= this.config.highOccupancyCriticalPct)
      .map(zoneDensity => `Zone ${zoneDensity.zoneId} (${Math.round(zoneDensity.densityPercentage)}%)`);

    const constraintDetail = fullZones.length > 0
      ? ` Constrained zones: ${fullZones.join(', ')}.`
      : '';

    return [{
      category: 'simulation',
      severity: 'critical',
      priority: 'immediate',
      zoneId: 'ALL',
      condition: `Simulation: ${simulation.totalUnitsUnplaced} of ${totalRequested} unit(s) cannot be placed. ${availableSlots} empty slot(s) remain system-wide.${constraintDetail}`,
      risk: 'Inbound shipment will be partially rejected. Receiving dock backup is likely.',
      action: availableSlots === 0
        ? 'Defer inbound shipment. Create capacity by redistributing or shipping out slow-moving stock before receiving.'
        : `Defer shipment or free ${simulation.totalUnitsUnplaced} slot(s) before receiving. Remove slow-moving stock from constrained zones.`,
    }];
  }

  // ─── R8: Simulation Zone Impact ────────────────────────────────────

  private ruleSimulationZoneImpact(
    simulation: SimulationResult,
    occupancy: OccupancySnapshot,
  ): Recommendation[] {
    if (simulation.allocations.length === 0) return [];

    const zoneRecommendations: Recommendation[] = [];

    const unitsPerZone = new Map<string, number>();
    for (const allocation of simulation.allocations) {
      unitsPerZone.set(allocation.zoneId, (unitsPerZone.get(allocation.zoneId) ?? 0) + 1);
    }

    for (const zone of occupancy.zoneDensities) {
      const addedUnits = unitsPerZone.get(zone.zoneId);
      if (!addedUnits) continue;

      const currentDensity = zone.densityPercentage;
      const projectedOccupied = zone.occupiedBins + addedUnits;
      const projectedDensity = zone.totalBins > 0 ? (projectedOccupied / zone.totalBins) * 100 : 0;

      if (currentDensity < this.config.highOccupancyCriticalPct && projectedDensity >= this.config.highOccupancyCriticalPct) {
        zoneRecommendations.push({
          category: 'simulation',
          severity: 'warning',
          priority: 'next',
          zoneId: zone.zoneId,
          condition: `Simulation projects Zone ${zone.zoneId} from ${Math.round(currentDensity)}% → ${Math.round(projectedDensity)}% occupancy (+${addedUnits} unit(s)).`,
          risk: `Zone ${zone.zoneId} will exceed ${this.config.highOccupancyCriticalPct}% threshold, blocking future allocations in this zone.`,
          action: `Split inbound across multiple zones. Do not allocate all ${addedUnits} unit(s) to Zone ${zone.zoneId}.`,
        });
      }
    }

    return zoneRecommendations;
  }

  // ─── Helpers ───────────────────────────────────────────────────────

  private findLowestDensityZone(zones: ZoneDensity[]): ZoneDensity | null {
    if (zones.length === 0) return null;
    return zones.reduce(
      (lowestZone, currentZone) => (currentZone.densityPercentage < lowestZone.densityPercentage ? currentZone : lowestZone),
      zones[0],
    );
  }

  /**
   * Builds a redistribution action with an explicit target zone when available.
   * Falls back to generic wording only if no valid target exists.
   */
  private buildRedistributionAction(
    sourceZoneId: string,
    targetZone: ZoneDensity | null,
    prefix = 'Relocate stock from',
  ): string {
    if (!targetZone || targetZone.zoneId === sourceZoneId) {
      return `${prefix} Zone ${sourceZoneId} to available zones.`;
    }

    const available = targetZone.totalBins - targetZone.occupiedBins;
    const targetPct = Math.round(targetZone.densityPercentage);
    return `${prefix} Zone ${sourceZoneId} to Zone ${targetZone.zoneId} (${targetPct}% occupied, ${available} slot(s) available).`;
  }

  /**
   * Sorts by priority (immediate > next > monitor), then severity (critical > warning > info).
   */
  private sortByPriorityThenSeverity(recommendations: Recommendation[]): Recommendation[] {
    const priorityOrder: Record<string, number> = { immediate: 0, next: 1, monitor: 2 };
    const severityOrder: Record<string, number> = { critical: 0, warning: 1, info: 2 };

    return recommendations.sort((a, b) => {
      const pd = (priorityOrder[a.priority] ?? 3) - (priorityOrder[b.priority] ?? 3);
      if (pd !== 0) return pd;
      return (severityOrder[a.severity] ?? 3) - (severityOrder[b.severity] ?? 3);
    });
  }
}
