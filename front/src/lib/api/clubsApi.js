import { createApi } from "@reduxjs/toolkit/query/react";
import { backendBaseQuery } from "@/lib/api/baseQuery";

export const clubsApi = createApi({
  reducerPath: "clubsApi",
  baseQuery: backendBaseQuery,
  tagTypes: ["Clubs"],
  endpoints: (builder) => ({
    getClubs: builder.query({
      query: () => "clubs",
      providesTags: ["Clubs"],
    }),
    getClub: builder.query({
      query: (id) => `clubs/${id}`,
      providesTags: (result, error, id) => [{ type: "Clubs", id }],
    }),
    createClub: builder.mutation({
      query: (body) => ({
        url: "clubs",
        method: "POST",
        body,
      }),
      invalidatesTags: ["Clubs"],
    }),
    updateClub: builder.mutation({
      query: ({ id, body }) => ({
        url: `clubs/${id}`,
        method: "PUT",
        body,
      }),
      invalidatesTags: (result, error, { id }) => [
        "Clubs",
        { type: "Clubs", id },
      ],
    }),
    deleteClub: builder.mutation({
      query: (id) => ({
        url: `clubs/${id}`,
        method: "DELETE",
      }),
      invalidatesTags: ["Clubs"],
    }),
  }),
});

export const {
  useGetClubsQuery,
  useGetClubQuery,
  useCreateClubMutation,
  useUpdateClubMutation,
  useDeleteClubMutation,
} = clubsApi;
