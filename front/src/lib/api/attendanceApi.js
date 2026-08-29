import { createBackendApi } from "@/lib/api/baseQuery";

export const attendanceApi = createBackendApi({
  reducerPath: "attendanceApi",
  tagTypes: ["Attendance"],
  endpoints: (builder) => ({
    qrCheckIn: builder.mutation({
      query: (body) => ({
        url: "qr/check-in",
        method: "POST",
        body,
      }),
      invalidatesTags: ["Attendance"],
    }),
    qrCheckOut: builder.mutation({
      query: (body) => ({
        url: "qr/check-out",
        method: "POST",
        body,
      }),
      invalidatesTags: ["Attendance"],
    }),
    getMemberSubscriptions: builder.query({
      query: (memberId) => `reception/members/${memberId}/subscriptions`,
      providesTags: ["Attendance"],
    }),
    getMember: builder.query({
      query: (memberId) => `members/${memberId}`,
      providesTags: ["Attendance"],
    }),
    getMemberAttendances: builder.query({
      query: (memberId) => ({
        url: `attendances/history`,
        params: {
          attendable_type: "member",
          attendable_id: memberId,
        },
      }),
      providesTags: ["Attendance"],
    }),
    getAttendances: builder.query({
      query: (params = {}) => ({
        url: "attendances/history",
        params,
      }),
      providesTags: ["Attendance"],
    }),
    manualCheckIn: builder.mutation({
      query: (body) => ({
        url: "attendances/check-in",
        method: "POST",
        body,
      }),
      invalidatesTags: ["Attendance"],
    }),
    manualCheckOut: builder.mutation({
      query: (attendanceId) => ({
        url: `attendances/check-out/${attendanceId}`,
        method: "POST",
      }),
      invalidatesTags: ["Attendance"],
    }),
    bulkCheckOut: builder.mutation({
      query: (body) => ({
        url: "attendances/bulk-check-out",
        method: "POST",
        body,
      }),
      invalidatesTags: ["Attendance"],
    }),
    rollbackAttendance: builder.mutation({
      query: ({ attendanceId, playerSubscriptionIds }) => ({
        url: `reception/attendances/${attendanceId}/rollback`,
        method: "DELETE",
        ...(playerSubscriptionIds.length
          ? { body: { player_subscription_ids: playerSubscriptionIds } }
          : {}),
      }),
      invalidatesTags: ["Attendance"],
    }),
    deductAttendance: builder.mutation({
      query: ({ attendanceId, body }) => ({
        url: `reception/attendances/${attendanceId}/deduct`,
        method: "POST",
        body,
      }),
      invalidatesTags: ["Attendance"],
    }),
  }),
});

export const {
  useQrCheckInMutation,
  useQrCheckOutMutation,
  useGetMemberSubscriptionsQuery,
  useGetMemberQuery,
  useGetMemberAttendancesQuery,
  useGetAttendancesQuery,
  useManualCheckInMutation,
  useManualCheckOutMutation,
  useBulkCheckOutMutation,
  useRollbackAttendanceMutation,
  useDeductAttendanceMutation,
} = attendanceApi;
