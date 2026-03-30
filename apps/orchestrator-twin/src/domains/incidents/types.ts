export type IncidentSeverity = 'low' | 'medium' | 'high' | 'critical';
export type IncidentStatus = 'open' | 'in_review' | 'resolved' | 'closed';

export interface ApiIncident {
  id: string;
  locationId: string;
  productId: string;
  type: string;
  severity: IncidentSeverity;
  status: IncidentStatus;
  description: string;
  quantityAffected: number;
  reportedBy: string;
  createdAt: string;
}

export interface LocationIncidents {
  locationId: string;
  openCount: number;
  highestSeverity: IncidentSeverity | null;
  incidentIds: string[];
}

export interface ZoneIncidents {
  zoneId: string;
  totalOpenCount: number;
  criticalCount: number;
}

export interface IncidentsSnapshot {
  locations: Record<string, LocationIncidents>;
  zoneIncidents: ZoneIncidents[];
}
