import type { ApiIncident, IncidentsSnapshot, LocationIncidents, ZoneIncidents, IncidentSeverity } from './types';
import type { LayoutSnapshot } from '../layout/types';

const severityRank: Record<IncidentSeverity, number> = {
  'low': 1,
  'medium': 2,
  'high': 3,
  'critical': 4
};

/**
 * Transforms flat API incidents into an IncidentsSnapshot.
 * Maps open incidents and severities to physical locations and zones.
 */
export function mapIncidentsToSnapshot(
  apiIncidents: ApiIncident[],
  layout: LayoutSnapshot
): IncidentsSnapshot {
  
  // 1. Filter out closed/resolved incidents as we only visualize open/active issues
  const activeIncidents = apiIncidents.filter(apiIncident => 
    apiIncident.status === 'open' || apiIncident.status === 'in_review'
  );

  const locations: Record<string, LocationIncidents> = {};

  for (const activeIncident of activeIncidents) {
    if (!activeIncident.locationId) continue;
    
    if (!locations[activeIncident.locationId]) {
      locations[activeIncident.locationId] = {
        locationId: activeIncident.locationId,
        openCount: 0,
        highestSeverity: null,
        incidentIds: []
      };
    }

    const locationState = locations[activeIncident.locationId];
    locationState.openCount++;
    locationState.incidentIds.push(activeIncident.id);

    const currentRank = locationState.highestSeverity ? severityRank[locationState.highestSeverity] || 0 : 0;
    const newRank = severityRank[activeIncident.severity] || 0;
    if (newRank > currentRank) {
      locationState.highestSeverity = activeIncident.severity;
    }
  }

  const zoneIncidents: ZoneIncidents[] = [];

  for (const warehouse of layout.warehouses) {
    for (const zone of warehouse.zones) {
      let totalOpenCount = 0;
      let criticalCount = 0;

      for (const aisle of zone.aisles) {
        for (const rack of aisle.racks) {
          for (const bin of rack.bins) {
            const locationIncidentState = locations[bin.locationId];
            if (locationIncidentState) {
              totalOpenCount += locationIncidentState.openCount;
              if (locationIncidentState.highestSeverity === 'critical') {
                criticalCount++;
              }
            }
          }
        }
      }

      zoneIncidents.push({
        zoneId: zone.id,
        totalOpenCount,
        criticalCount
      });
    }
  }

  return {
    locations,
    zoneIncidents
  };
}
