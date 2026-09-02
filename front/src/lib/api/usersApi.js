import { createBackendApi } from "@/lib/api/baseQuery";

export const usersApi = createBackendApi({
  reducerPath: "usersApi",
  tagTypes: ["Users", "Roles"],
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
  }),
});

export const { useGetUsersQuery, useGetRolesQuery } = usersApi;
