import { createApi, fetchBaseQuery } from "@reduxjs/toolkit/query/react";
import { getAuthHeader } from "@/lib/authStorage";

export const membersApi = createApi({
  reducerPath: "membersApi",
  baseQuery: fetchBaseQuery({
    baseUrl: "/api/backend",
    prepareHeaders: (headers) => {
      const authHeader = getAuthHeader();

      if (authHeader) {
        headers.set("Authorization", authHeader);
      }

      return headers;
    },
  }),
  tagTypes: ["Members"],
  endpoints: (builder) => ({
    getMembers: builder.query({
      query: (params = {}) => {
        const searchParams = new URLSearchParams();
        Object.entries(params).forEach(([key, value]) => {
          if (value !== undefined && value !== null && value !== "") {
            searchParams.set(key, String(value));
          }
        });
        const queryString = searchParams.toString();
        return `members${queryString ? `?${queryString}` : ""}`;
      },
      providesTags: ["Members"],
    }),
    createPlayer: builder.mutation({
      query: (body) => ({
        url: "players",
        method: "POST",
        body,
      }),
      invalidatesTags: ["Members"],
    }),
    updatePlayer: builder.mutation({
      query: ({ id, body }) => ({
        url: `players/${id}`,
        method: "PUT",
        body,
      }),
      invalidatesTags: ["Members"],
    }),
    deleteMember: builder.mutation({
      query: (id) => ({
        url: `members/${id}`,
        method: "DELETE",
      }),
      invalidatesTags: ["Members"],
    }),
  }),
});

export const {
  useGetMembersQuery,
  useCreatePlayerMutation,
  useUpdatePlayerMutation,
  useDeleteMemberMutation,
} = membersApi;
