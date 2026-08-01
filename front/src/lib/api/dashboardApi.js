import { createApi } from "@reduxjs/toolkit/query/react";
import { backendBaseQuery } from "@/lib/api/baseQuery";

export const dashboardApi = createApi({
  reducerPath: "dashboardApi",
  baseQuery: backendBaseQuery,
  endpoints: (builder) => ({
    getDashboardStatsStream: builder.query({
      queryFn: () => ({ data: null }),
      async onCacheEntryAdded(
        arg,
        { updateCachedData, cacheDataLoaded, cacheEntryRemoved }
      ) {
        try {
          await cacheDataLoaded;

          // Build query string for SSE if needed (e.g. branch_id)
          const searchParams = new URLSearchParams(arg || {}).toString();
          const url = `/api/backend/attendance-manager/dashboard-stats-stream${
            searchParams ? `?${searchParams}` : ""
          }`;

          const eventSource = new EventSource(url, { withCredentials: true });

          eventSource.onmessage = (event) => {
            try {
              const response = JSON.parse(event.data);
              
              if ((response.success || response.status === "success") && response.data) {
                updateCachedData((draft) => {
                  Object.assign(draft, response.data);
                });
              }
            } catch (err) {
              console.error("Error parsing SSE data", err);
            }
          };

          eventSource.onerror = (error) => {
            console.error("SSE Error:", error);
            eventSource.close();
          };

          await cacheEntryRemoved;
          eventSource.close();
        } catch (error) {
          // no-op
        }
      },
    }),
  }),
});

export const { useGetDashboardStatsStreamQuery } = dashboardApi;
