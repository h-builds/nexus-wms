export type HeatmapIntensity = 'low' | 'medium' | 'high';

export interface ZoneHeatmapEntry {
  zoneId: string;
  intensity: HeatmapIntensity;
  score: number;
}

export interface HeatmapConfig {
  mediumThreshold: number;
  highThreshold: number;
}

export const DEFAULT_HEATMAP_CONFIG: HeatmapConfig = {
  mediumThreshold: 40,
  highThreshold: 70,
};
