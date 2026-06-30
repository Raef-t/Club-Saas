import { createApi, fetchBaseQuery } from "@reduxjs/toolkit/query/react";
import { getAuthHeader } from "@/lib/authStorage";

export const activitiesApi = createApi({
  reducerPath: "activitiesApi",
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
  tagTypes: ["Activities"],
  endpoints: (builder) => ({
    getActivities: builder.query({
      query: () => "activities",
      providesTags: ["Activities"],
    }),
    getActivity: builder.query({
      query: (id) => `activities/${id}`,
      providesTags: (result, error, id) => [{ type: "Activities", id }],
    }),
    createActivity: builder.mutation({
      query: (body) => ({
        url: "activities",
        method: "POST",
        body,
      }),
      invalidatesTags: ["Activities"],
    }),
    updateActivity: builder.mutation({
      query: ({ id, body }) => ({
        url: `activities/${id}`,
        method: "PUT",
        body,
      }),
      invalidatesTags: (result, error, { id }) => [
        "Activities",
        { type: "Activities", id },
      ],
    }),
    deleteActivity: builder.mutation({
      query: (id) => ({
        url: `activities/${id}`,
        method: "DELETE",
      }),
      invalidatesTags: ["Activities"],
    }),
  }),
});

export const {
  useGetActivitiesQuery,
  useGetActivityQuery,
  useCreateActivityMutation,
  useUpdateActivityMutation,
  useDeleteActivityMutation,
} = activitiesApi;
