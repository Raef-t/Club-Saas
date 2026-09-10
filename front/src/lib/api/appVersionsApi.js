import { createBackendApi } from "@/lib/api/baseQuery";

export const appVersionsApi = createBackendApi({
  reducerPath: "appVersionsApi",
  tagTypes: ["AppVersions"],
  endpoints: (builder) => ({
    getAppVersions: builder.query({
      query: (params = {}) => ({
        url: "app-versions",
        params,
      }),
      providesTags: ["AppVersions"],
    }),

    getAppVersion: builder.query({
      query: (id) => `app-versions/${id}`,
      providesTags: (result, error, id) => [{ type: "AppVersions", id }],
    }),

    createAppVersion: builder.mutation({
      query: (body) => ({
        url: "app-versions",
        method: "POST",
        body,
      }),
      invalidatesTags: ["AppVersions"],
    }),

    updateAppVersion: builder.mutation({
      query: ({ id, body }) => {
        const isFormData = typeof FormData !== "undefined" && body instanceof FormData;
        if (isFormData && !body.has("_method")) {
          body.append("_method", "PUT");
        }
        return {
          url: `app-versions/${id}`,
          method: isFormData ? "POST" : "PUT",
          body,
        };
      },
      invalidatesTags: (result, error, { id }) => ["AppVersions", { type: "AppVersions", id }],
    }),

    toggleAppVersionStatus: builder.mutation({
      query: (id) => ({
        url: `app-versions/${id}/toggle-status`,
        method: "PATCH",
      }),
      invalidatesTags: (result, error, id) => ["AppVersions", { type: "AppVersions", id }],
    }),

    deleteAppVersion: builder.mutation({
      query: (id) => ({
        url: `app-versions/${id}`,
        method: "DELETE",
      }),
      invalidatesTags: ["AppVersions"],
    }),
  }),
});

export const {
  useGetAppVersionsQuery,
  useGetAppVersionQuery,
  useCreateAppVersionMutation,
  useUpdateAppVersionMutation,
  useToggleAppVersionStatusMutation,
  useDeleteAppVersionMutation,
} = appVersionsApi;
