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
      query: (id) => ({
        url: `player-subscriptions/${id}`,
        method: "DELETE",
      }),
      invalidatesTags: (result, error, id) => [
        { type: "PlayerSubscriptions", id },
        "PlayerSubscriptions",
      ],
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
