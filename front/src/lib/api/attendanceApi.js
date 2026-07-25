import { createApi, fetchBaseQuery } from "@reduxjs/toolkit/query/react";
import { getAuthHeader } from "@/lib/authStorage";

export const attendanceApi = createApi({
  reducerPath: "attendanceApi",
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
      query: (memberId) => `reception/members/${memberId}/attendances`,
      providesTags: ["Attendance"],
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
  useDeductAttendanceMutation,
} = attendanceApi;
