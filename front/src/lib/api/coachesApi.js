import { createApi } from "@reduxjs/toolkit/query/react";
import { backendBaseQuery } from "@/lib/api/baseQuery";

export const coachesApi = createApi({
  reducerPath: "coachesApi",
  baseQuery: backendBaseQuery,
  tagTypes: ["Coaches"],
  endpoints: (builder) => ({
    getCoaches: builder.query({
      query: (params) => ({
        url: "coaches",
        params,
      }),
      providesTags: ["Coaches"],
    }),
    getCoach: builder.query({
      query: (id) => `coaches/${id}`,
      providesTags: (result, error, id) => [{ type: "Coaches", id }],
    }),
    createCoach: builder.mutation({
      query: (body) => ({
        url: "coaches",
        method: "POST",
        body,
      }),
      invalidatesTags: ["Coaches"],
    }),
    updateCoachBasic: builder.mutation({
      query: ({ id, body }) => ({
        url: `coaches/${id}`,
        method: "PUT",
        body,
      }),
      invalidatesTags: (result, error, { id }) => ["Coaches", { type: "Coaches", id }],
    }),
    updateCoachDetails: builder.mutation({
      query: ({ id, body }) => ({
        url: `coaches/${id}/details`,
        method: "PUT",
        body,
      }),
      invalidatesTags: (result, error, { id }) => ["Coaches", { type: "Coaches", id }],
    }),
    updateCoach: builder.mutation({
      query: ({ id, body }) => ({
        url: `coaches/${id}`,
        method: "PATCH",
        body,
      }),
      invalidatesTags: (result, error, { id }) => ["Coaches", { type: "Coaches", id }],
    }),
    updateCoachPhoto: builder.mutation({
      query: ({ id, body }) => ({
        url: `coaches/${id}/photo`,
        method: "POST",
        body,
      }),
      invalidatesTags: (result, error, { id }) => ["Coaches", { type: "Coaches", id }],
    }),
    deleteCoach: builder.mutation({
      query: (id) => ({
        url: `coaches/${id}`,
        method: "DELETE",
      }),
      invalidatesTags: ["Coaches"],
    }),
  }),
});

export const {
  useGetCoachesQuery,
  useGetCoachQuery,
  useCreateCoachMutation,
  useUpdateCoachBasicMutation,
  useUpdateCoachDetailsMutation,
  useUpdateCoachMutation,
  useUpdateCoachPhotoMutation,
  useDeleteCoachMutation,
} = coachesApi;
