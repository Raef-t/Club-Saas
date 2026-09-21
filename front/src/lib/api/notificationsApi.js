import { createBackendApi } from "@/lib/api/baseQuery";

export const notificationsApi = createBackendApi({
  reducerPath: "notificationsApi",
  tagTypes: ["Notifications"],
  endpoints: (builder) => ({
    getNotifications: builder.query({
      query: (params = {}) => ({
        url: "notifications",
        params,
      }),
      providesTags: ["Notifications"],
    }),
    markNotificationRead: builder.mutation({
      query: (recipientId) => ({
        url: `notifications/${recipientId}/read`,
        method: "PATCH",
      }),
      invalidatesTags: ["Notifications"],
    }),
  }),
});

export const { useGetNotificationsQuery, useMarkNotificationReadMutation } = notificationsApi;
