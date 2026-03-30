import { fetchFromApi } from '../shared/api';
import type { ApiStockItem, OccupancySnapshot } from './types';
import type { LayoutSnapshot } from '../layout/types';
import { mapStockToOccupancySnapshot } from './mapper';

/**
 * Pure service for fetching and interpreting structural stock occupancy.
 */
export class OccupancyService {
  /**
   * Fetches the entire warehouse inventory to calculate current occupancy graph.
   * Requires layout topology to understand spatial distribution (zones).
   */
  async getOccupancySnapshot(layoutSnapshot: LayoutSnapshot): Promise<OccupancySnapshot> {
    const inventory: ApiStockItem[] = [];
    let page = 1;
    const perPage = 100;
    let hasMore = true;

    try {
      // Drain the inventory endpoint. Real-world systems might use materialized views 
      // for this, but MVP relies on explicit pagination consumption.
      while (hasMore) {
        const response = await fetchFromApi<ApiStockItem[]>('/inventory', { page, per_page: perPage });
        
        inventory.push(...response.data);
        
        if (response.meta.currentPage >= response.meta.totalPages) {
          hasMore = false;
        } else {
          page++;
        }
      }
    } catch (apiError) {
      throw new Error(`Inventory infrastructure unavailable: Unable to load physical occupancy state.`, { cause: apiError });
    }

    return mapStockToOccupancySnapshot(inventory, layoutSnapshot);
  }
}
