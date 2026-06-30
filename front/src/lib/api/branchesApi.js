import { createApi, fetchBaseQuery } from "@reduxjs/toolkit/query/react";
import { getAuthHeader } from "@/lib/authStorage";

export const branchesApi = createApi({
  reducerPath: "branchesApi",
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
  tagTypes: ["Branches"],
  endpoints: (builder) => ({
    getBranches: builder.query({
      query: () => "branches",
      providesTags: ["Branches"],
    }),
    getBranch: builder.query({
      query: (id) => `branches/${id}`,
      providesTags: (result, error, id) => [{ type: "Branches", id }],
    }),
    createBranch: builder.mutation({
      query: (body) => ({
        url: "branches",
        method: "POST",
        body,
      }),
      invalidatesTags: ["Branches"],
    }),
    updateBranch: builder.mutation({
      query: ({ id, body }) => ({
        url: `branches/${id}`,
        method: "PUT",
        body,
      }),
      invalidatesTags: (result, error, { id }) => [
        "Branches",
        { type: "Branches", id },
      ],
    }),
    deleteBranch: builder.mutation({
      query: (id) => ({
        url: `branches/${id}`,
        method: "DELETE",
      }),
      invalidatesTags: ["Branches"],
    }),
    toggleBranchStatus: builder.mutation({
      query: (id) => ({
        url: `branches/${id}/toggle-status`,
        method: "POST",
      }),
      invalidatesTags: (result, error, id) => [
        "Branches",
        { type: "Branches", id },
      ],
    }),
  }),
});

export const {
  useGetBranchesQuery,
  useGetBranchQuery,
  useCreateBranchMutation,
  useUpdateBranchMutation,
  useDeleteBranchMutation,
  useToggleBranchStatusMutation,
} = branchesApi;
