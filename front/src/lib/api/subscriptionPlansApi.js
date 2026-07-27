import { createApi } from "@reduxjs/toolkit/query/react";
import { backendBaseQuery } from "@/lib/api/baseQuery";

export const subscriptionPlansApi = createApi({
  reducerPath: "subscriptionPlansApi",
  baseQuery: backendBaseQuery,
  tagTypes: ["SubscriptionPlans"],
  endpoints: (builder) => ({
    getSubscriptionPlans: builder.query({
      query: (params = {}) => ({
        url: "subscription-plans",
        params,
      }),
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
