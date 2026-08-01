import { createApi } from "@reduxjs/toolkit/query/react";
import { backendBaseQuery } from "@/lib/api/baseQuery";

export const reportsApi = createApi({
  reducerPath: "reportsApi",
  baseQuery: backendBaseQuery,
  endpoints: (builder) => ({
    getShiftAttendanceReport: builder.query({
      query: (params) => ({
        url: "reports/shifts/attendance",
        params,
      }),
    }),
  }),
});

export const { useGetShiftAttendanceReportQuery } = reportsApi;
