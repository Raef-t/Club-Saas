import { createApi, fetchBaseQuery } from "@reduxjs/toolkit/query/react";
import { getAuthHeader } from "@/lib/authStorage";

export const subscriptionPlansApi = createApi({
  reducerPath: "subscriptionPlansApi",
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
  tagTypes: ["SubscriptionPlans"],
  endpoints: (builder) => ({
    getSubscriptionPlans: builder.query({
      query: () => "subscription-plans",
      providesTags: ["SubscriptionPlans"],
    }),
    getSubscriptionPlan: builder.query({
      query: (id) => `subscription-plans/${id}`,
      providesTags: (result, error, id) => [{ type: "SubscriptionPlans", id }],
    }),
    createSubscriptionPlan: builder.mutation({
      query: (body) => ({
        url: "subscription-plans",
        method: "POST",
        body,
      }),
      invalidatesTags: ["SubscriptionPlans"],
    }),
    updateSubscriptionPlan: builder.mutation({
      query: ({ id, body }) => ({
        url: `subscription-plans/${id}`,
        method: "PUT",
        body,
      }),
      invalidatesTags: (result, error, { id }) => [
        "SubscriptionPlans",
        { type: "SubscriptionPlans", id },
      ],
    }),
    deleteSubscriptionPlan: builder.mutation({
      query: (id) => ({
        url: `subscription-plans/${id}`,
        method: "DELETE",
      }),
      invalidatesTags: ["SubscriptionPlans"],
    }),
  }),
});

export const {
  useCreateSubscriptionPlanMutation,
  useDeleteSubscriptionPlanMutation,
  useGetSubscriptionPlanQuery,
  useGetSubscriptionPlansQuery,
  useUpdateSubscriptionPlanMutation,
} = subscriptionPlansApi;
