import { createApi } from "@reduxjs/toolkit/query/react";
import { backendBaseQuery } from "@/lib/api/baseQuery";

export const scheduleApi = createApi({
  reducerPath: "scheduleApi",
  baseQuery: backendBaseQuery,
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
