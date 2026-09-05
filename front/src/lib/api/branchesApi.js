import { createBackendApi } from "@/lib/api/baseQuery";

export const branchesApi = createBackendApi({
  reducerPath: "branchesApi",
  tagTypes: ["Branches", "BranchSettings", "BranchShifts", "BranchHolidays"],
  endpoints: (builder) => ({
    getBranches: builder.query({
      query: (params = {}) => ({ url: "branches", params }),
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
      invalidatesTags: (result, error, { id }) => ["Branches", { type: "Branches", id }],
    }),
    deleteBranch: builder.mutation({
      query: ({ id, confirmation }) => ({
        url: `branches/${id}`,
        method: "DELETE",
        params: { confirmation },
      }),
      invalidatesTags: ["Branches"],
    }),
    toggleBranchStatus: builder.mutation({
      query: (id) => ({
        url: `branches/${id}/toggle-status`,
        method: "POST",
      }),
      invalidatesTags: (result, error, id) => ["Branches", { type: "Branches", id }],
    }),
    getBranchSettings: builder.query({
      query: (branchId) => `branches/${branchId}/settings`,
      providesTags: (result, error, branchId) => [{ type: "BranchSettings", id: branchId }],
    }),
    updateBranchSettings: builder.mutation({
      query: ({ branchId, body }) => ({
        url: `branches/${branchId}/settings`,
        method: "PUT",
        body,
      }),
      invalidatesTags: (result, error, { branchId }) => [{ type: "BranchSettings", id: branchId }],
    }),
    getBranchShifts: builder.query({
      query: (branchId) => `branches/${branchId}/shifts`,
      providesTags: (result, error, branchId) => [{ type: "BranchShifts", id: branchId }],
    }),
    createBranchShift: builder.mutation({
      query: ({ branchId, body }) => ({
        url: `branches/${branchId}/shifts`,
        method: "POST",
        body,
      }),
      invalidatesTags: (result, error, { branchId }) => [{ type: "BranchShifts", id: branchId }],
    }),
    updateBranchShift: builder.mutation({
      query: ({ branchId, shiftId, body }) => ({
        url: `branches/${branchId}/shifts/${shiftId}`,
        method: "PUT",
        body,
      }),
      invalidatesTags: (result, error, { branchId }) => [{ type: "BranchShifts", id: branchId }],
    }),
    deleteBranchShift: builder.mutation({
      query: ({ branchId, shiftId }) => ({
        url: `branches/${branchId}/shifts/${shiftId}`,
        method: "DELETE",
      }),
      invalidatesTags: (result, error, { branchId }) => [{ type: "BranchShifts", id: branchId }],
    }),
    getBranchHolidays: builder.query({
      query: (branchId) => `branches/${branchId}/holidays`,
      providesTags: (result, error, branchId) => [{ type: "BranchHolidays", id: branchId }],
    }),
    createBranchHoliday: builder.mutation({
      query: ({ branchId, body }) => ({
        url: `branches/${branchId}/holidays`,
        method: "POST",
        body,
      }),
      invalidatesTags: (result, error, { branchId }) => [{ type: "BranchHolidays", id: branchId }],
    }),
    updateBranchHoliday: builder.mutation({
      query: ({ branchId, holidayId, body }) => ({
        url: `holidays/${holidayId}`,
        method: "PUT",
        body,
      }),
      invalidatesTags: (result, error, { branchId }) => [{ type: "BranchHolidays", id: branchId }],
    }),
    deleteBranchHoliday: builder.mutation({
      query: ({ branchId, holidayId }) => ({
        url: `holidays/${holidayId}`,
        method: "DELETE",
      }),
      invalidatesTags: (result, error, { branchId }) => [{ type: "BranchHolidays", id: branchId }],
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
  useGetBranchSettingsQuery,
  useUpdateBranchSettingsMutation,
  useGetBranchShiftsQuery,
  useLazyGetBranchShiftsQuery,
  useCreateBranchShiftMutation,
  useUpdateBranchShiftMutation,
  useDeleteBranchShiftMutation,
  useGetBranchHolidaysQuery,
  useCreateBranchHolidayMutation,
  useUpdateBranchHolidayMutation,
  useDeleteBranchHolidayMutation,
} = branchesApi;
