import { createBackendApi } from "@/lib/api/baseQuery";

export const activitiesApi = createBackendApi({
  reducerPath: "activitiesApi",
  tagTypes: ["Activities"],
  endpoints: (builder) => ({
    getActivities: builder.query({
      query: (params = {}) => ({
        url: "activities",
        params,
      }),
      providesTags: ["Activities"],
    }),
    getActivityTypes: builder.query({
      query: () => "activity-types",
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
      invalidatesTags: (result, error, { id }) => ["Activities", { type: "Activities", id }],
    }),
    deleteActivity: builder.mutation({
      query: ({ id, confirmation }) => ({
        url: `activities/${id}`,
        method: "DELETE",
        params: { confirmation },
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
  useGetActivityTypesQuery,
} = activitiesApi;
