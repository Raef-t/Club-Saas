import { createBackendApi } from "@/lib/api/baseQuery";

export const lockersApi = createBackendApi({
  reducerPath: "lockersApi",
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
        method: "PUT",
        body,
      }),
      invalidatesTags: ["Lockers"],
    }),
    deleteLocker: builder.mutation({
      query: ({ id, confirmation }) => ({
        url: `lockers/${id}`,
        method: "DELETE",
        params: { confirmation },
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
      query: (request) => {
        const { id, body } =
          request && typeof request === "object" ? request : { id: request, body: undefined };
        return {
          url: `lockers/${id}/reservations/current`,
          method: "DELETE",
          body,
        };
      },
      invalidatesTags: ["Lockers"],
    }),
  }),
});

export const {
  useGetLockersQuery,
  useLazyGetLockersQuery,
  useGetLockerQuery,
  useCreateLockerMutation,
  useUpdateLockerMutation,
  useDeleteLockerMutation,
  useToggleLockerStatusMutation,
  useReserveLockerMutation,
  useReleaseLockerReservationMutation,
} = lockersApi;
