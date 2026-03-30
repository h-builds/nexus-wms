export const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000';

export interface PaginatedMeta {
  currentPage: number;
  perPage: number;
  totalItems: number;
  totalPages: number;
}

export interface PaginatedResponse<T> {
  data: T;
  meta: PaginatedMeta;
}

/**
 * Shared generic fetch wrapper to communicate with the operational backend.
 */
export async function fetchFromApi<T>(
  endpoint: string,
  params: Record<string, string | number> = {},
): Promise<PaginatedResponse<T>> {
  const url = new URL('/api' + endpoint, API_BASE_URL);

  Object.entries(params).forEach(([key, value]) => {
    url.searchParams.append(key, String(value));
  });

  const response = await fetch(url.toString(), {
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
    },
  });

  if (!response.ok) {
    throw new Error(
      'API Error: ' + response.status + ' ' + response.statusText + ' on ' + endpoint,
    );
  }

  let json;
  try {
    json = await response.json();
  } catch (parseError) {
    throw new Error('API Response format invalid: Expected JSON from ' + endpoint, { cause: parseError });
  }
  
  return json as PaginatedResponse<T>;
}
