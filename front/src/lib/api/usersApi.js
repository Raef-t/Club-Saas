import { createBackendApi } from "@/lib/api/baseQuery";
import { authApi } from "@/lib/api/authApi";

async function refreshCurrentProfileAfterMutation(_, { dispatch, queryFulfilled }) {
  try {
    await queryFulfilled;
    dispatch(authApi.util.invalidateTags(["Profile"]));
  } catch {
    // The mutation error is exposed to its caller; keep the current profile cache.
  }
}

export const usersApi = createBackendApi({
  reducerPath: "usersApi",
  tagTypes: ["Users", "Roles", "UserRoles"],
  endpoints: (builder) => ({
    getUsers: builder.query({
      query: (params = {}) => ({
        url: "users",
        params,
      }),
      providesTags: ["Users"],
    }),
    getRoles: builder.query({
      query: () => ({
        url: "roles",
      }),
      providesTags: ["Roles"],
    }),
    getUserRoles: builder.query({
      query: (userId) => `users/${userId}/roles`,
      providesTags: (result, error, userId) => [{ type: "UserRoles", id: userId }],
    }),
    assignUserRole: builder.mutation({
      query: ({ userId, role }) => ({
        url: `users/${userId}/roles`,
        method: "POST",
        body: { role },
      }),
      invalidatesTags: (result, error, { userId }) => ["Users", { type: "UserRoles", id: userId }],
      onQueryStarted: refreshCurrentProfileAfterMutation,
    }),
    revokeUserRole: builder.mutation({
      query: ({ userId, role }) => ({
        url: `users/${userId}/roles`,
        method: "DELETE",
        body: { role },
      }),
      invalidatesTags: (result, error, { userId }) => ["Users", { type: "UserRoles", id: userId }],
      onQueryStarted: refreshCurrentProfileAfterMutation,
    }),
  }),
});

export const {
  useGetUsersQuery,
  useGetRolesQuery,
  useGetUserRolesQuery,
  useAssignUserRoleMutation,
  useRevokeUserRoleMutation,
} = usersApi;
