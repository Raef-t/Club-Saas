import { createApi } from "@reduxjs/toolkit/query/react";
import { backendBaseQuery } from "@/lib/api/baseQuery";

function getPlanList(response) {
  if (Array.isArray(response?.data)) return response.data;
  if (Array.isArray(response?.data?.data)) return response.data.data;
  if (Array.isArray(response)) return response;
  return null;
}

function getPlanRecord(response) {
  const record = response?.data?.data || response?.data || response;
  return record && typeof record === "object" && !Array.isArray(record) ? record : null;
}

/**
 * Keeps an updated plan in an already loaded list even when the backend's list
 * endpoint temporarily omits inactive records.
 */
export function mergeSubscriptionPlanIntoResponse(response, plan) {
  const plans = getPlanList(response);
  if (!plans || !plan?.id) return;

  const index = plans.findIndex((item) => String(item.id) === String(plan.id));
  if (index === -1) {
    plans.unshift(plan);
    return;
  }

  plans[index] = {
    ...plans[index],
    ...plan,
  };
}

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
      async onQueryStarted({ id, body }, { dispatch, queryFulfilled }) {
        const optimisticPlan = { id, ...body };
        const queryArgs = [{}];

        if (body.branch_id !== null && body.branch_id !== undefined && body.branch_id !== "") {
          queryArgs.push({ branch_id: String(body.branch_id) });
        }

        const patches = queryArgs.map((queryArg) =>
          dispatch(
            subscriptionPlansApi.util.updateQueryData(
              "getSubscriptionPlans",
              queryArg,
              (draft) => mergeSubscriptionPlanIntoResponse(draft, optimisticPlan),
            ),
          ),
        );

        try {
          const { data } = await queryFulfilled;
          const updatedPlan = getPlanRecord(data);

          if (updatedPlan) {
            queryArgs.forEach((queryArg) => {
              dispatch(
                subscriptionPlansApi.util.updateQueryData(
                  "getSubscriptionPlans",
                  queryArg,
                  (draft) => mergeSubscriptionPlanIntoResponse(draft, updatedPlan),
                ),
              );
            });
          }
        } catch {
          patches.forEach((patchResult) => patchResult.undo());
        }
      },
      invalidatesTags: (result, error, { id }) => [{ type: "SubscriptionPlans", id }],
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
