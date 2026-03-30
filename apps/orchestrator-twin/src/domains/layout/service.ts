import { fetchFromApi } from '../shared/api';
import type { ApiWarehouseLocation, LayoutSnapshot } from './types';
import { mapLocationsToLayoutSnapshot } from './mapper';

/**
 * Pure service for fetching and interpreting structural layout.
 */
export class LayoutService {
  /**
   * Fetches all locations and maps them to a normalized spatial format.
   * This is a read-heavy operation interpreting remote data.
   */
  async getLayoutSnapshot(): Promise<LayoutSnapshot> {
    const locations: ApiWarehouseLocation[] = [];
    let page = 1;
    const perPage = 100;
    let hasMore = true;

    try {
      // Drain the endpoint for full layout load, respecting MVP pagination envelopes.
      while (hasMore) {
        const response = await fetchFromApi<ApiWarehouseLocation[]>('/locations', { page, per_page: perPage });
        
        locations.push(...response.data);
        
        if (response.meta.currentPage >= response.meta.totalPages) {
          hasMore = false;
        } else {
          page++;
        }
      }
    } catch (apiError) {
      throw new Error(`Twin infrastructure unavailable: Unable to load physical warehouse layout.`, { cause: apiError });
    }

    return mapLocationsToLayoutSnapshot(locations);
  }
}
