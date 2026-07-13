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
    createLocker: builder.mutation({
      query: (body) => ({
        url: "lockers",
        method: "POST",
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
  }),
});

export const {
  useGetLockersQuery,
  useCreateLockerMutation,
  useDeleteLockerMutation,
  useToggleLockerStatusMutation,
} = lockersApi;
