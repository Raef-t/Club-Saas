import { createBackendApi } from "@/lib/api/baseQuery";

export const reportsApi = createBackendApi({
  reducerPath: "reportsApi",
  endpoints: (builder) => ({
    getShiftAttendanceReport: builder.query({
      query: (params) => ({
        url: "reports/shifts/attendance",
        params,
      }),
    }),
    getCoachSubscriptionsReport: builder.query({
      query: (params) => ({
        url: "reports/coaches/subscriptions",
        params,
      }),
    }),
  }),
});

export const { useGetShiftAttendanceReportQuery, useGetCoachSubscriptionsReportQuery } = reportsApi;
