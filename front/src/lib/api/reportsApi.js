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
    getSubscriptionsReport: builder.query({
      query: (params = {}) => ({
        url: "reports/subscriptions",
        params,
      }),
    }),
    getSubscriptionRenewalStatusReport: builder.query({
      query: (params = {}) => ({
        url: "reports/subscriptions/renewal-status",
        params,
      }),
    }),
    getTimeCapacityReport: builder.query({
      query: (params = {}) => ({
        url: "reports/sessions/time-capacity",
        params,
      }),
    }),
    getPeakHoursReport: builder.query({
      query: (params = {}) => ({
        url: "reports/attendance/peak-hours",
        params,
      }),
    }),
    getFrozenTerminatedSubscriptionsReport: builder.query({
      query: (params = {}) => ({
        url: "reports/subscriptions/frozen-terminated",
        params,
      }),
    }),
  }),
});

export const {
  useGetShiftAttendanceReportQuery,
  useGetCoachSubscriptionsReportQuery,
  useGetSubscriptionsReportQuery,
  useGetSubscriptionRenewalStatusReportQuery,
  useGetTimeCapacityReportQuery,
  useGetPeakHoursReportQuery,
  useGetFrozenTerminatedSubscriptionsReportQuery,
} = reportsApi;
