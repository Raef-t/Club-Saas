import { createApi, fetchBaseQuery } from "@reduxjs/toolkit/query/react";

/**
 * Shared RTK Query transport. Authentication is supplied by the HttpOnly
 * session cookie and is never exposed to browser JavaScript.
 */
export const backendBaseQuery = fetchBaseQuery({
  baseUrl: "/api/backend",
  credentials: "same-origin",
});

/**
 * Creates an API slice for the shared backend.
 *
 * Backend resources are related, while their RTK Query caches live in separate
 * slices. Re-fetching on subscription mount keeps a page fresh after a mutation
 * in another slice (for example, deleting a member before opening subscriptions).
 */
export function createBackendApi(options) {
  return createApi({
    refetchOnMountOrArgChange: true,
    ...options,
    baseQuery: options.baseQuery || backendBaseQuery,
  });
}
