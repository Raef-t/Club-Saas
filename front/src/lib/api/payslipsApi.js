import { createBackendApi } from "@/lib/api/baseQuery";

export const payslipsApi = createBackendApi({
  reducerPath: "payslipsApi",
  tagTypes: ["Payslips"],
  endpoints: (builder) => ({
    getPayslips: builder.query({
      query: () => "payslips",
      providesTags: ["Payslips"],
    }),
    generatePayslips: builder.mutation({
      query: (body) => ({
        url: "payslips/generate",
        method: "POST",
        body,
      }),
    }),
    updatePayslip: builder.mutation({
      query: ({ id, body }) => ({
        url: `payslips/${id}`,
        method: "PUT",
        body,
      }),
      invalidatesTags: ["Payslips"],
    }),
    confirmPayslips: builder.mutation({
      query: (body) => ({
        url: "payslips/confirm",
        method: "POST",
        body,
      }),
      invalidatesTags: ["Payslips"],
    }),
  }),
});

export const {
  useGetPayslipsQuery,
  useGeneratePayslipsMutation,
  useUpdatePayslipMutation,
  useConfirmPayslipsMutation,
} = payslipsApi;
