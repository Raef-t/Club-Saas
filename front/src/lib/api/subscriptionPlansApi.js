import { createBackendApi } from "@/lib/api/baseQuery";

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

function getSuspensionRecord(response) {
  const record = response?.data?.data || response?.data || response;
  return record && typeof record === "object" && !Array.isArray(record) ? record : null;
}

function getMatchingPlan(response, planId) {
  const plans = getPlanList(response);
  if (plans) {
    return plans.find((plan) => String(plan.id) === String(planId)) || null;
  }

  const plan = getPlanRecord(response);
  return String(plan?.id) === String(planId) ? plan : null;
}

export function mergeSubscriptionPlanSuspensionIntoResponse(response, planId, suspension) {
  const plan = getMatchingPlan(response, planId);
  if (!plan || !suspension?.id) return;

  plan.is_suspended = true;
  plan.active_suspension_id = suspension.id;
  plan.active_suspension = suspension;

  const suspensions = Array.isArray(plan.suspensions) ? plan.suspensions : [];
  const existingIndex = suspensions.findIndex((item) => String(item.id) === String(suspension.id));

  if (existingIndex === -1) {
    plan.suspensions = [suspension, ...suspensions];
  } else {
    suspensions[existingIndex] = { ...suspensions[existingIndex], ...suspension };
  }
}

export function clearSubscriptionPlanSuspensionFromResponse(response, planId, suspensionId) {
  const plan = getMatchingPlan(response, planId);
  if (!plan) return;

  plan.is_suspended = false;
  plan.active_suspension_id = null;
  plan.active_suspension = null;

  if (Array.isArray(plan.suspensions)) {
    plan.suspensions = plan.suspensions.map((suspension) =>
      String(suspension.id) === String(suspensionId)
        ? { ...suspension, status: "completed", actual_end_date: new Date().toISOString() }
        : suspension,
    );
  }
}

function updateSubscriptionPlanCaches(dispatch, getState, planId, updater) {
  const queryArgs = subscriptionPlansApi.util.selectCachedArgsForQuery(
    getState(),
    "getSubscriptionPlans",
  );

  queryArgs.forEach((queryArg) => {
    dispatch(
      subscriptionPlansApi.util.updateQueryData("getSubscriptionPlans", queryArg, (draft) => {
        updater(draft);
      }),
    );
  });

  const detailQueryArgs = subscriptionPlansApi.util
    .selectCachedArgsForQuery(getState(), "getSubscriptionPlan")
    .filter((queryArg) => String(queryArg) === String(planId));

  detailQueryArgs.forEach((queryArg) => {
    dispatch(
      subscriptionPlansApi.util.updateQueryData("getSubscriptionPlan", queryArg, (draft) => {
        updater(draft);
      }),
    );
  });
}

export function normalizeSubscriptionPlanPlayersResponse(response) {
  const payload = response?.data?.data || response?.data || response;
  const players = Array.isArray(payload?.players) ? payload.players : [];
  const total = Number(payload?.total_active_subscribers);

  return {
    plan_id: payload?.plan_id ?? null,
    plan_name: payload?.plan_name ?? "",
    total_active_subscribers: Number.isFinite(total) ? total : players.length,
    players,
  };
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

export const subscriptionPlansApi = createBackendApi({
  reducerPath: "subscriptionPlansApi",
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
    getSubscriptionPlanPlayers: builder.query({
      query: (id) => `subscription-plans/${id}/players`,
      transformResponse: normalizeSubscriptionPlanPlayersResponse,
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
            subscriptionPlansApi.util.updateQueryData("getSubscriptionPlans", queryArg, (draft) =>
              mergeSubscriptionPlanIntoResponse(draft, optimisticPlan),
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
      invalidatesTags: (result, error, { id }) => [
        { type: "SubscriptionPlans", id },
        "SubscriptionPlans",
      ],
    }),
    deleteSubscriptionPlan: builder.mutation({
      query: ({ id, confirmation }) => ({
        url: `subscription-plans/${id}`,
        method: "DELETE",
        params: { confirmation },
      }),
      invalidatesTags: ["SubscriptionPlans"],
    }),
    suspendSubscriptionPlan: builder.mutation({
      query: ({ id, body }) => ({
        url: `subscription-plans/${id}/suspend`,
        method: "POST",
        body,
      }),
      async onQueryStarted({ id }, { dispatch, getState, queryFulfilled }) {
        try {
          const { data } = await queryFulfilled;
          const suspension = getSuspensionRecord(data);
          if (!suspension?.id) return;

          updateSubscriptionPlanCaches(dispatch, getState, id, (draft) => {
            mergeSubscriptionPlanSuspensionIntoResponse(draft, id, suspension);
          });
        } catch {
          // The mutation error is handled by the consuming hook.
        }
      },
      invalidatesTags: (result, error, { id }) => [
        { type: "SubscriptionPlans", id },
        "SubscriptionPlans",
      ],
    }),
    resumeSubscriptionPlan: builder.mutation({
      query: ({ id, suspensionId }) => ({
        url: `subscription-plans/${id}/suspensions/${suspensionId}`,
        method: "DELETE",
      }),
      async onQueryStarted({ id, suspensionId }, { dispatch, getState, queryFulfilled }) {
        try {
          await queryFulfilled;
          updateSubscriptionPlanCaches(dispatch, getState, id, (draft) => {
            clearSubscriptionPlanSuspensionFromResponse(draft, id, suspensionId);
          });
        } catch {
          // The mutation error is handled by the consuming hook.
        }
      },
      invalidatesTags: (result, error, { id }) => [{ type: "SubscriptionPlans", id }],
    }),
  }),
});

export const {
  useCreateSubscriptionPlanMutation,
  useDeleteSubscriptionPlanMutation,
  useGetSubscriptionPlanQuery,
  useGetSubscriptionPlanPlayersQuery,
  useGetSubscriptionPlansQuery,
  useResumeSubscriptionPlanMutation,
  useSuspendSubscriptionPlanMutation,
  useUpdateSubscriptionPlanMutation,
} = subscriptionPlansApi;
