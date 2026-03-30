import { fetchFromApi } from '../shared/api';
import type { ApiIncident, IncidentsSnapshot } from './types';
import type { LayoutSnapshot } from '../layout/types';
import { mapIncidentsToSnapshot } from './mapper';

/**
 * Pure service for fetching and interpreting structural incident mapping.
 */
export class IncidentsService {
  /**
   * Fetches actively tracked incidents to map them to physical infrastructure.
   */
  async getIncidentsSnapshot(layoutSnapshot: LayoutSnapshot): Promise<IncidentsSnapshot> {
    const incidents: ApiIncident[] = [];
    let page = 1;
    const perPage = 100;
    let hasMore = true;

    try {
      while (hasMore) {
        // Per API specifications, we could query just open incidents, but MVP supports full drainage
        const response = await fetchFromApi<ApiIncident[]>('/incidents', { page, per_page: perPage, status: 'open' });
        
        incidents.push(...response.data);
        
        if (response.meta.currentPage >= response.meta.totalPages) {
          hasMore = false;
        } else {
          page++;
        }
      }
    } catch (apiError) {
      throw new Error(`Operational capability degraded: Unable to load spatial incident overlays.`, { cause: apiError });
    }

    return mapIncidentsToSnapshot(incidents, layoutSnapshot);
  }
}
