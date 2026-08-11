import { createApi } from "@reduxjs/toolkit/query/react";
import { backendBaseQuery } from "@/lib/api/baseQuery";

export const staffApi = createApi({
  reducerPath: "staffApi",
  baseQuery: backendBaseQuery,
  tagTypes: ["Staff"],
  endpoints: (builder) => ({
    getStaff: builder.query({
      query: (params = {}) => ({
        url: "staff",
        params,
      }),
      providesTags: (result) => {
        const rows = Array.isArray(result?.data?.data)
          ? result.data.data
          : Array.isArray(result?.data)
            ? result.data
            : [];

        return [{ type: "Staff", id: "LIST" }, ...rows.map(({ id }) => ({ type: "Staff", id }))];
      },
    }),
    getStaffMember: builder.query({
      query: (id) => `staff/${id}`,
      providesTags: (result, error, id) => [{ type: "Staff", id }],
    }),
    createStaffMember: builder.mutation({
      query: (body) => ({
        url: "staff",
        method: "POST",
        body,
      }),
      invalidatesTags: [{ type: "Staff", id: "LIST" }],
    }),
    updateStaffMember: builder.mutation({
      query: ({ id, body }) => ({
        url: `staff/${id}`,
        method: "PUT",
        body,
      }),
      invalidatesTags: (result, error, { id }) => [
        { type: "Staff", id },
        { type: "Staff", id: "LIST" },
      ],
    }),
    deleteStaffMember: builder.mutation({
      query: (id) => ({
        url: `staff/${id}`,
        method: "DELETE",
      }),
      invalidatesTags: (result, error, id) => [
        { type: "Staff", id },
        { type: "Staff", id: "LIST" },
      ],
    }),
  }),
});

export const {
  useGetStaffQuery,
  useGetStaffMemberQuery,
  useCreateStaffMemberMutation,
  useUpdateStaffMemberMutation,
  useDeleteStaffMemberMutation,
} = staffApi;
