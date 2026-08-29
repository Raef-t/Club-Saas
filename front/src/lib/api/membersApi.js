import { createBackendApi } from "@/lib/api/baseQuery";

export const membersApi = createBackendApi({
  reducerPath: "membersApi",
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
        url: "members/register",
        method: "POST",
        body,
      }),
      invalidatesTags: ["Members"],
    }),
    updatePlayer: builder.mutation({
      query: ({ id, body }) => ({
        url: `members/${id}`,
        method: "PUT",
        body,
      }),
      invalidatesTags: ["Members"],
    }),
    deleteMember: builder.mutation({
      query: ({ id, confirmation }) => ({
        url: `members/${id}`,
        method: "DELETE",
        params: { confirmation },
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
