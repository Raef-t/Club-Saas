import { createBackendApi } from "@/lib/api/baseQuery";

export const rolesApi = createBackendApi({
  reducerPath: "rolesApi",
  tagTypes: ["Roles", "Role", "Permissions"],
  endpoints: (builder) => ({
    getRoles: builder.query({
      query: () => "roles",
      providesTags: ["Roles"],
    }),
    getRole: builder.query({
      query: (id) => `roles/${id}`,
      providesTags: (result, error, id) => [{ type: "Role", id }],
    }),
    getPermissions: builder.query({
      query: (params = {}) => ({
        url: "permissions",
        params,
      }),
      providesTags: ["Permissions"],
    }),
    createRole: builder.mutation({
      query: (body) => ({
        url: "roles",
        method: "POST",
        body,
      }),
      invalidatesTags: ["Roles"],
    }),
    updateRole: builder.mutation({
      query: ({ id, ...body }) => ({
        url: `roles/${id}`,
        method: "PUT",
        body,
      }),
      invalidatesTags: (result, error, { id }) => ["Roles", { type: "Role", id }],
    }),
    syncRolePermissions: builder.mutation({
      query: ({ id, permissions }) => ({
        url: `roles/${id}/permissions`,
        method: "PUT",
        body: { permissions },
      }),
      invalidatesTags: (result, error, { id }) => ["Roles", { type: "Role", id }],
    }),
    deleteRole: builder.mutation({
      query: (id) => ({
        url: `roles/${id}`,
        method: "DELETE",
      }),
      invalidatesTags: ["Roles"],
    }),
  }),
});

export const {
  useGetRolesQuery,
  useGetRoleQuery,
  useGetPermissionsQuery,
  useCreateRoleMutation,
  useUpdateRoleMutation,
  useSyncRolePermissionsMutation,
  useDeleteRoleMutation,
} = rolesApi;
