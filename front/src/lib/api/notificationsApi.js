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
  }),
});

export const { useGetNotificationsQuery } = notificationsApi;
