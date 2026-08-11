import { createApi } from "@reduxjs/toolkit/query/react";
import { backendBaseQuery } from "@/lib/api/baseQuery";

export const authApi = createApi({
  reducerPath: "authApi",
  baseQuery: backendBaseQuery,
  endpoints: (builder) => ({
    logout: builder.mutation({
      query: () => ({
        url: "auth/logout",
        method: "POST",
      }),
    }),
    changePassword: builder.mutation({
      query: (body) => ({
        url: "auth/change-password",
        method: "POST",
        body,
      }),
    }),
    getProfile: builder.query({
      query: () => "auth/me",
    }),
  }),
});

export const { useLogoutMutation, useChangePasswordMutation, useGetProfileQuery } = authApi;
