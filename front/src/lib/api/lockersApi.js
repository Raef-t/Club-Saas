import { createApi, fetchBaseQuery } from "@reduxjs/toolkit/query/react";
import { getAuthHeader } from "@/lib/authStorage";

export const lockersApi = createApi({
  reducerPath: "lockersApi",
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
  tagTypes: ["Lockers"],
  endpoints: (builder) => ({
    getLockers: builder.query({
      query: (params = {}) => {
        const searchParams = new URLSearchParams();
        Object.entries(params).forEach(([key, value]) => {
          if (value !== undefined && value !== null && value !== "") {
            searchParams.set(key, String(value));
          }
        });
        const queryString = searchParams.toString();
        return `lockers${queryString ? `?${queryString}` : ""}`;
      },
      providesTags: ["Lockers"],
    }),
    getLocker: builder.query({
      query: (id) => `lockers/${id}`,
      providesTags: ["Lockers"],
    }),
    createLocker: builder.mutation({
      query: (body) => ({
        url: "lockers",
        method: "POST",
        body,
      }),
      invalidatesTags: ["Lockers"],
    }),
    updateLocker: builder.mutation({
      query: ({ id, ...body }) => ({
        url: `lockers/${id}`,
        method: "PUT", // or PATCH depending on the API, usually PUT for update
        body,
      }),
      invalidatesTags: ["Lockers"],
    }),
    deleteLocker: builder.mutation({
      query: (id) => ({
        url: `lockers/${id}`,
        method: "DELETE",
      }),
      invalidatesTags: ["Lockers"],
    }),
    toggleLockerStatus: builder.mutation({
      query: (id) => ({
        url: `lockers/${id}/toggle-status`,
        method: "PATCH",
      }),
      invalidatesTags: ["Lockers"],
    }),
    reserveLocker: builder.mutation({
      query: ({ id, ...body }) => ({
        url: `lockers/${id}/reservations`,
        method: "POST",
        body,
      }),
      invalidatesTags: ["Lockers"],
    }),
    releaseLockerReservation: builder.mutation({
      query: (id) => ({
        url: `lockers/${id}/reservations/current`,
        method: "DELETE",
      }),
      invalidatesTags: ["Lockers"],
    }),
  }),
});

export const {
  useGetLockersQuery,
  useGetLockerQuery,
  useCreateLockerMutation,
  useUpdateLockerMutation,
  useDeleteLockerMutation,
  useToggleLockerStatusMutation,
  useReserveLockerMutation,
  useReleaseLockerReservationMutation,
} = lockersApi;
