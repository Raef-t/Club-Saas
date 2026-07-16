import { createApi, fetchBaseQuery } from "@reduxjs/toolkit/query/react";
import { getAuthHeader } from "@/lib/authStorage";

export const scheduleApi = createApi({
  reducerPath: "scheduleApi",
  baseQuery: fetchBaseQuery({
    baseUrl: "/api/backend",
    prepareHeaders: (headers) => {
      const authHeader = getAuthHeader();

      if (authHeader) {
        headers.set("Authorization", authHeader);
      }

      return headers;
    },
  }),
  tagTypes: ["Schedule"],
  endpoints: (builder) => ({
    getSchedule: builder.query({
      query: () => "session-templates/schedule",
      providesTags: ["Schedule"],
    }),
  }),
});

export const { useGetScheduleQuery } = scheduleApi;
