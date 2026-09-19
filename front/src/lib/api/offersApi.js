import { createBackendApi } from "@/lib/api/baseQuery";

export const offersApi = createBackendApi({
  reducerPath: "offersApi",
  tagTypes: ["Offers", "PlayerSubscriptions"],
  endpoints: (builder) => ({
    getOffers: builder.query({
      query: (params = {}) => {
        const searchParams = new URLSearchParams();

        Object.entries(params).forEach(([key, value]) => {
          if (value !== undefined && value !== null && value !== "") {
            searchParams.set(key, String(value));
          }
        });

        const queryString = searchParams.toString();
        return `offers${queryString ? `?${queryString}` : ""}`;
      },
      providesTags: ["Offers"],
    }),

    getOffer: builder.query({
      query: (id) => `offers/${id}`,
      providesTags: (result, error, id) => [{ type: "Offers", id }],
    }),

    getTrashedOffers: builder.query({
      query: (params = {}) => {
        const searchParams = new URLSearchParams();

        Object.entries(params).forEach(([key, value]) => {
          if (value !== undefined && value !== null && value !== "") {
            searchParams.set(key, String(value));
          }
        });

        const queryString = searchParams.toString();
        return `offers/trashed${queryString ? `?${queryString}` : ""}`;
      },
      providesTags: ["Offers"],
    }),

    createOffer: builder.mutation({
      query: (body) => ({
        url: "offers",
        method: "POST",
        body,
      }),
      invalidatesTags: ["Offers"],
    }),

    updateOffer: builder.mutation({
      query: ({ id, body }) => ({
        url: `offers/${id}`,
        method: "PUT",
        body,
      }),
      invalidatesTags: (result, error, { id }) => [
        { type: "Offers", id },
        "Offers",
      ],
    }),

    deleteOffer: builder.mutation({
      query: (arg) => {
        if (typeof arg === "object" && arg !== null) {
          const { id, confirm } = arg;
          const searchParams = new URLSearchParams();
          if (confirm) {
            searchParams.set("confirm", String(confirm));
          }
          const qs = searchParams.toString();
          return {
            url: `offers/${id}${qs ? `?${qs}` : ""}`,
            method: "DELETE",
          };
        }

        return {
          url: `offers/${arg}?confirm=delete`,
          method: "DELETE",
        };
      },
      invalidatesTags: (result, error, arg) => {
        const id = typeof arg === "object" && arg !== null ? arg.id : arg;
        return [{ type: "Offers", id }, "Offers", "PlayerSubscriptions"];
      },
    }),

    restoreOffer: builder.mutation({
      query: (id) => ({
        url: `offers/${id}/restore`,
        method: "POST",
      }),
      invalidatesTags: ["Offers", "PlayerSubscriptions"],
    }),

    subscribeToOffer: builder.mutation({
      query: ({ id, body }) => ({
        url: `offers/${id}/subscribe`,
        method: "POST",
        body,
      }),
      invalidatesTags: ["Offers", "PlayerSubscriptions"],
    }),
  }),
});

export const {
  useGetOffersQuery,
  useGetOfferQuery,
  useGetTrashedOffersQuery,
  useCreateOfferMutation,
  useUpdateOfferMutation,
  useDeleteOfferMutation,
  useRestoreOfferMutation,
  useSubscribeToOfferMutation,
} = offersApi;

export const useSubscribeOfferMutation = useSubscribeToOfferMutation;

