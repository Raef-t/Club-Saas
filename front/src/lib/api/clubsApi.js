import { createBackendApi } from "@/lib/api/baseQuery";

export const clubsApi = createBackendApi({
  reducerPath: "clubsApi",
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
      query: ({ id, body }) => {
        const isFormData = typeof FormData !== "undefined" && body instanceof FormData;
        if (isFormData && !body.has("_method")) {
          body.append("_method", "PUT");
        }
        return {
          url: `clubs/${id}`,
          method: isFormData ? "POST" : "PUT",
          body,
        };
      },
      invalidatesTags: (result, error, { id }) => ["Clubs", { type: "Clubs", id }],
    }),
    updateClubLogo: builder.mutation({
      query: ({ id, logo }) => {
        const body = new FormData();
        body.append("logo", logo);

        return {
          url: `clubs/${id}/logo`,
          method: "POST",
          body,
        };
      },
      invalidatesTags: (result, error, { id }) => ["Clubs", { type: "Clubs", id }],
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
  useUpdateClubLogoMutation,
  useDeleteClubMutation,
} = clubsApi;
