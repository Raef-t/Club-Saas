import { createBackendApi } from "@/lib/api/baseQuery";

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
      query: ({ userId, roles }) => ({
        url: `users/${userId}/roles`,
        method: "POST",
        body: { roles },
      }),
      invalidatesTags: (result, error, { userId }) => [
        "Users",
        { type: "UserRoles", id: userId },
      ],
    }),
  }),
});

export const {
  useGetUsersQuery,
  useGetRolesQuery,
  useGetUserRolesQuery,
  useAssignUserRoleMutation,
} = usersApi;
