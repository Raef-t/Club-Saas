import { createBackendApi } from "@/lib/api/baseQuery";

export const scheduleApi = createBackendApi({
  reducerPath: "scheduleApi",
  tagTypes: ["Schedule"],
  endpoints: (builder) => ({
    getSchedule: builder.query({
      query: (branchId) => ({
        url: "session-templates/schedule",
        params: branchId && branchId !== "all" ? { branch_id: String(branchId) } : undefined,
      }),
      providesTags: ["Schedule"],
    }),
  }),
});

export const { useGetScheduleQuery } = scheduleApi;
