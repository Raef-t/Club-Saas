import { createApi, fetchBaseQuery } from "@reduxjs/toolkit/query/react";
import { getAuthHeader } from "@/lib/authStorage";

export const coachesApi = createApi({
  reducerPath: "coachesApi",
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
  tagTypes: ["Coaches"],
  endpoints: (builder) => ({
    getCoaches: builder.query({
      query: () => "coaches",
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
      invalidatesTags: (result, error, { id }) => [
        "Coaches",
        { type: "Coaches", id },
      ],
    }),
    updateCoachDetails: builder.mutation({
      query: ({ id, body }) => ({
        url: `coaches/${id}/details`,
        method: "PUT",
        body,
      }),
      invalidatesTags: (result, error, { id }) => [
        "Coaches",
        { type: "Coaches", id },
      ],
    }),
    deleteCoach: builder.mutation({
      query: (id) => ({
        url: `coaches/${id}`,
        method: "DELETE",
      }),
      invalidatesTags: ["Coaches"],
    }),
    addCoachActivities: builder.mutation({
      query: ({ id, activity_ids }) => ({
        url: `coaches/${id}/activities`,
        method: "POST",
        body: { activity_ids },
      }),
      invalidatesTags: (result, error, { id }) => [
        "Coaches",
        { type: "Coaches", id },
      ],
    }),
    deleteCoachActivity: builder.mutation({
      query: ({ id, activityId }) => ({
        url: `coaches/${id}/activities/${activityId}`,
        method: "DELETE",
      }),
      invalidatesTags: (result, error, { id }) => [
        "Coaches",
        { type: "Coaches", id },
      ],
    }),
  }),
});

export const {
  useGetCoachesQuery,
  useGetCoachQuery,
  useCreateCoachMutation,
  useUpdateCoachBasicMutation,
  useUpdateCoachDetailsMutation,
  useDeleteCoachMutation,
  useAddCoachActivitiesMutation,
  useDeleteCoachActivityMutation,
} = coachesApi;
