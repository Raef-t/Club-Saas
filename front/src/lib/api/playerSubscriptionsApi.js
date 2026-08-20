import { createApi } from "@reduxjs/toolkit/query/react";
import { backendBaseQuery } from "@/lib/api/baseQuery";

export const playerSubscriptionsApi = createApi({
  reducerPath: "playerSubscriptionsApi",
  baseQuery: backendBaseQuery,
  tagTypes: ["PlayerSubscriptions"],
  endpoints: (builder) => ({
    getPlayerSubscriptions: builder.query({
      query: (params = {}) => {
        const searchParams = new URLSearchParams();

        Object.entries(params).forEach(([key, value]) => {
          if (value !== undefined && value !== null && value !== "") {
            searchParams.set(key, String(value));
          }
        });

        const queryString = searchParams.toString();
        return `player-subscriptions${queryString ? `?${queryString}` : ""}`;
      },
      providesTags: ["PlayerSubscriptions"],
    }),
    getPlayerSubscription: builder.query({
      query: (id) => `player-subscriptions/${id}`,
      providesTags: (result, error, id) => [{ type: "PlayerSubscriptions", id }],
    }),
    createPlayerSubscription: builder.mutation({
      query: (body) => ({
        url: "player-subscriptions",
        method: "POST",
        body,
      }),
      invalidatesTags: ["PlayerSubscriptions"],
    }),
    updatePlayerSubscription: builder.mutation({
      query: ({ id, body }) => ({
        url: `player-subscriptions/${id}`,
        method: "PUT",
        body,
      }),
      invalidatesTags: (result, error, { id }) => [
        { type: "PlayerSubscriptions", id },
        "PlayerSubscriptions",
      ],
    }),
    freezeSubscription: builder.mutation({
      query: ({ id, body }) => ({
        url: `player-subscriptions/${id}/freeze`,
        method: "POST",
        body,
      }),
      invalidatesTags: (result, error, { id }) => [
        { type: "PlayerSubscriptions", id },
        "PlayerSubscriptions",
      ],
    }),
    unfreezeSubscription: builder.mutation({
      query: (id) => ({
        url: `player-subscriptions/${id}/unfreeze`,
        method: "POST",
      }),
      invalidatesTags: (result, error, id) => [
        { type: "PlayerSubscriptions", id },
        "PlayerSubscriptions",
      ],
    }),
    cancelSubscription: builder.mutation({
      query: (id) => ({
        url: `player-subscriptions/${id}/cancel`,
        method: "POST",
      }),
      invalidatesTags: (result, error, id) => [
        { type: "PlayerSubscriptions", id },
        "PlayerSubscriptions",
      ],
    }),
    deletePlayerSubscription: builder.mutation({
      query: (arg) => {
        if (typeof arg === "object" && arg !== null) {
          const { id, is_refunded, reason, params, body } = arg;
          const searchParams = new URLSearchParams();

          if (params) {
            Object.entries(params).forEach(([key, value]) => {
              if (value !== undefined && value !== null && value !== "") {
                searchParams.set(key, String(value));
              }
            });
          }

          if (is_refunded !== undefined && !searchParams.has("is_refunded")) {
            searchParams.set("is_refunded", String(Boolean(is_refunded)));
          }

          const queryString = searchParams.toString();
          let finalBody = body;
          if (finalBody === undefined && (is_refunded !== undefined || reason !== undefined)) {
            finalBody = {
              ...(is_refunded !== undefined ? { is_refunded: Boolean(is_refunded) } : {}),
              ...(reason !== undefined && String(reason).trim() !== ""
                ? { reason: String(reason).trim() }
                : {}),
            };
          }

          return {
            url: `player-subscriptions/${id}${queryString ? `?${queryString}` : ""}`,
            method: "DELETE",
            body: finalBody,
          };
        }

        return {
          url: `player-subscriptions/${arg}`,
          method: "DELETE",
        };
      },
      invalidatesTags: (result, error, arg) => {
        const id = typeof arg === "object" && arg !== null ? arg.id : arg;
        return [
          { type: "PlayerSubscriptions", id },
          "PlayerSubscriptions",
        ];
      },
    }),
  }),
});

export const {
  useGetPlayerSubscriptionQuery,
  useGetPlayerSubscriptionsQuery,
  useCreatePlayerSubscriptionMutation,
  useUpdatePlayerSubscriptionMutation,
  useFreezeSubscriptionMutation,
  useUnfreezeSubscriptionMutation,
  useCancelSubscriptionMutation,
  useDeletePlayerSubscriptionMutation,
} = playerSubscriptionsApi;
