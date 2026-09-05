import { createBackendApi } from "@/lib/api/baseQuery";

export const accountingApi = createBackendApi({
  reducerPath: "accountingApi",
  tagTypes: [
    "AccAccounts",
    "AccSafes",
    "AccJournals",
    "AccPartners",
    "AccCounterparties",
    "AccPeriods",
    "AccReconciliations",
    "AccSalaryPayments",
    "Payslips",
    "AccReports",
  ],
  endpoints: (builder) => ({
    // ==========================================
    // دليل وشجرة الحسابات — Chart of Accounts
    // ==========================================
    getAccounts: builder.query({
      query: (params = {}) => ({
        url: "accounting/accounts",
        params,
      }),
      providesTags: ["AccAccounts"],
    }),
    getAccount: builder.query({
      query: (id) => `accounting/accounts/${id}`,
      providesTags: (result, error, id) => [{ type: "AccAccounts", id }],
    }),
    createAccount: builder.mutation({
      query: (body) => ({
        url: "accounting/accounts",
        method: "POST",
        body,
      }),
      invalidatesTags: ["AccAccounts", "AccReports"],
    }),
    updateAccount: builder.mutation({
      query: ({ id, body }) => ({
        url: `accounting/accounts/${id}`,
        method: "PUT",
        body,
      }),
      invalidatesTags: (result, error, { id }) => [
        { type: "AccAccounts", id },
        "AccAccounts",
        "AccReports",
      ],
    }),
    getAccountLedger: builder.query({
      query: ({ id, ...params }) => ({
        url: `accounting/accounts/${id}/ledger`,
        params,
      }),
      providesTags: (result, error, { id }) => [
        { type: "AccAccounts", id: `ledger-${id}` },
        "AccJournals",
      ],
    }),

    // ==========================================
    // الصناديق والخزائن — Safes & Cashboxes
    // ==========================================
    getSafes: builder.query({
      query: (params = {}) => ({
        url: "accounting/safes",
        params,
      }),
      providesTags: ["AccSafes"],
    }),
    getSafe: builder.query({
      query: (id) => `accounting/safes/${id}`,
      providesTags: (result, error, id) => [{ type: "AccSafes", id }],
    }),
    createSafe: builder.mutation({
      query: (body) => ({
        url: "accounting/safes",
        method: "POST",
        body,
      }),
      invalidatesTags: ["AccSafes", "AccAccounts"],
    }),
    updateSafe: builder.mutation({
      query: ({ id, body }) => ({
        url: `accounting/safes/${id}`,
        method: "PUT",
        body,
      }),
      invalidatesTags: (result, error, { id }) => [
        { type: "AccSafes", id },
        "AccSafes",
      ],
    }),
    getSafeStatement: builder.query({
      query: ({ id, ...params }) => ({
        url: `accounting/safes/${id}/statement`,
        params,
      }),
      providesTags: (result, error, { id }) => [
        { type: "AccSafes", id: `statement-${id}` },
        "AccJournals",
      ],
    }),

    // ==========================================
    // سندات القيود اليومية — Journals & Vouchers
    // ==========================================
    getJournals: builder.query({
      query: (params = {}) => ({
        url: "accounting/journals",
        params,
      }),
      providesTags: ["AccJournals"],
    }),
    getJournal: builder.query({
      query: (id) => `accounting/journals/${id}`,
      providesTags: (result, error, id) => [{ type: "AccJournals", id }],
    }),
    createJournal: builder.mutation({
      query: (body) => ({
        url: "accounting/journals",
        method: "POST",
        body,
      }),
      invalidatesTags: [
        "AccJournals",
        "AccAccounts",
        "AccSafes",
        "AccPartners",
        "AccCounterparties",
        "AccReports",
      ],
    }),
    postJournal: builder.mutation({
      query: (id) => ({
        url: `accounting/journals/${id}/post`,
        method: "POST",
      }),
      invalidatesTags: [
        "AccJournals",
        "AccAccounts",
        "AccSafes",
        "AccPartners",
        "AccCounterparties",
        "AccReports",
      ],
    }),
    reverseJournal: builder.mutation({
      query: ({ id, body }) => ({
        url: `accounting/journals/${id}/reverse`,
        method: "POST",
        body,
      }),
      invalidatesTags: [
        "AccJournals",
        "AccAccounts",
        "AccSafes",
        "AccPartners",
        "AccReports",
      ],
    }),
    cancelJournal: builder.mutation({
      query: ({ id, body }) => ({
        url: `accounting/journals/${id}/cancel`,
        method: "POST",
        body,
      }),
      invalidatesTags: [
        "AccJournals",
        "AccAccounts",
        "AccSafes",
        "AccPartners",
        "AccReports",
      ],
    }),

    // ==========================================
    // الشركاء — Partners
    // ==========================================
    getPartners: builder.query({
      query: (params = {}) => ({
        url: "accounting/partners",
        params,
      }),
      providesTags: ["AccPartners"],
    }),
    getPartner: builder.query({
      query: (id) => `accounting/partners/${id}`,
      providesTags: (result, error, id) => [{ type: "AccPartners", id }],
    }),
    createPartner: builder.mutation({
      query: (body) => ({
        url: "accounting/partners",
        method: "POST",
        body,
      }),
      invalidatesTags: ["AccPartners", "AccAccounts"],
    }),
    updatePartner: builder.mutation({
      query: ({ id, body }) => ({
        url: `accounting/partners/${id}`,
        method: "PUT",
        body,
      }),
      invalidatesTags: (result, error, { id }) => [
        { type: "AccPartners", id },
        "AccPartners",
        "AccAccounts",
      ],
    }),
    deletePartner: builder.mutation({
      query: ({ id, confirmation }) => ({
        url: `accounting/partners/${id}`,
        method: "DELETE",
        params: { confirmation },
      }),
      invalidatesTags: ["AccPartners", "AccAccounts"],
    }),
    depositPartnerCapital: builder.mutation({
      query: (body) => ({
        url: "accounting/partners/deposit",
        method: "POST",
        body,
      }),
      invalidatesTags: ["AccPartners", "AccSafes", "AccJournals", "AccAccounts", "AccReports"],
    }),
    withdrawPartner: builder.mutation({
      query: (body) => ({
        url: "accounting/partners/withdrawal",
        method: "POST",
        body,
      }),
      invalidatesTags: ["AccPartners", "AccSafes", "AccJournals", "AccAccounts", "AccReports"],
    }),
    getPartnerStatement: builder.query({
      query: ({ id, ...params }) => ({
        url: `accounting/partners/${id}/statement`,
        params,
      }),
      providesTags: (result, error, { id }) => [
        { type: "AccPartners", id: `statement-${id}` },
        "AccJournals",
      ],
    }),

    // ==========================================
    // الأطراف — Counterparties (الذمم)
    // ==========================================
    getCounterparties: builder.query({
      query: (params = {}) => ({
        url: "accounting/counterparties",
        params,
      }),
      providesTags: ["AccCounterparties"],
    }),
    getCounterparty: builder.query({
      query: (id) => `accounting/counterparties/${id}`,
      providesTags: (result, error, id) => [{ type: "AccCounterparties", id }],
    }),
    createCounterparty: builder.mutation({
      query: (body) => ({
        url: "accounting/counterparties",
        method: "POST",
        body,
      }),
      invalidatesTags: ["AccCounterparties"],
    }),
    updateCounterparty: builder.mutation({
      query: ({ id, body }) => ({
        url: `accounting/counterparties/${id}`,
        method: "PUT",
        body,
      }),
      invalidatesTags: (result, error, { id }) => [
        { type: "AccCounterparties", id },
        "AccCounterparties",
      ],
    }),

    // ==========================================
    // الفترات المحاسبية — Accounting Periods
    // ==========================================
    getPeriods: builder.query({
      query: (params = {}) => ({
        url: "accounting/periods",
        params,
      }),
      providesTags: ["AccPeriods"],
    }),
    getPeriod: builder.query({
      query: (id) => `accounting/periods/${id}`,
      providesTags: (result, error, id) => [{ type: "AccPeriods", id }],
    }),
    createPeriod: builder.mutation({
      query: (body) => ({
        url: "accounting/periods",
        method: "POST",
        body,
      }),
      invalidatesTags: ["AccPeriods"],
    }),
    closePeriod: builder.mutation({
      query: (id) => ({
        url: `accounting/periods/${id}/close`,
        method: "POST",
      }),
      invalidatesTags: ["AccPeriods", "AccReports"],
    }),
    lockPeriod: builder.mutation({
      query: (id) => ({
        url: `accounting/periods/${id}/lock`,
        method: "POST",
      }),
      invalidatesTags: ["AccPeriods", "AccReports"],
    }),
    reopenPeriod: builder.mutation({
      query: (id) => ({
        url: `accounting/periods/${id}/reopen`,
        method: "POST",
      }),
      invalidatesTags: ["AccPeriods", "AccReports"],
    }),

    // ==========================================
    // تسوية ومطابقة الصناديق — Reconciliations
    // ==========================================
    getReconciliations: builder.query({
      query: (params = {}) => ({
        url: "accounting/reconciliations",
        params,
      }),
      providesTags: ["AccReconciliations"],
    }),
    getReconciliation: builder.query({
      query: (id) => `accounting/reconciliations/${id}`,
      providesTags: (result, error, id) => [{ type: "AccReconciliations", id }],
    }),
    createReconciliation: builder.mutation({
      query: (body) => ({
        url: "accounting/reconciliations",
        method: "POST",
        body,
      }),
      invalidatesTags: [
        "AccReconciliations",
        "AccSafes",
        "AccJournals",
        "AccAccounts",
      ],
    }),

    // ==========================================
    // رواتب الكوادر والموظفين — Salary Payments
    // ==========================================
    getSalaryPayments: builder.query({
      query: (params = {}) => ({
        url: "accounting/salary-payments",
        params,
      }),
      providesTags: ["AccSalaryPayments"],
    }),
    createSalaryPayment: builder.mutation({
      query: (body) => ({
        url: "accounting/salary-payments",
        method: "POST",
        body,
      }),
      invalidatesTags: [
        "AccSalaryPayments",
        "Payslips",
        "AccSafes",
        "AccJournals",
        "AccAccounts",
        "AccReports",
      ],
    }),
    deleteSalaryPayment: builder.mutation({
      query: ({ id, confirmation }) => ({
        url: `accounting/salary-payments/${id}`,
        method: "DELETE",
        params: { confirmation },
      }),
      invalidatesTags: [
        "AccSalaryPayments",
        "Payslips",
        "AccSafes",
        "AccJournals",
        "AccAccounts",
        "AccReports",
      ],
    }),

    // ==========================================
    // التقارير والقوائم المالية — Financial Reports
    // ==========================================
    getTrialBalance: builder.query({
      query: (params) => ({
        url: "accounting/reports/trial-balance",
        params,
      }),
      providesTags: ["AccReports"],
    }),
    getIncomeStatement: builder.query({
      query: (params) => ({
        url: "accounting/reports/income-statement",
        params,
      }),
      providesTags: ["AccReports"],
    }),
    getBalanceSheet: builder.query({
      query: (params) => ({
        url: "accounting/reports/balance-sheet",
        params,
      }),
      providesTags: ["AccReports"],
    }),
    getTransactionsByType: builder.query({
      query: (params) => ({
        url: "accounting/reports/transactions-by-type",
        params,
      }),
      providesTags: ["AccReports"],
    }),
    getAccountingDashboard: builder.query({
      query: (params = {}) => ({
        url: "accounting/dashboard",
        params,
      }),
      providesTags: ["AccReports", "AccJournals", "AccSafes", "AccSalaryPayments"],
    }),
  }),
});

export const {
  getAccountingDashboard,
  // Accounts
  useGetAccountsQuery,
  useGetAccountQuery,
  useCreateAccountMutation,
  useUpdateAccountMutation,
  useGetAccountLedgerQuery,

  // Safes
  useGetSafesQuery,
  useGetSafeQuery,
  useCreateSafeMutation,
  useUpdateSafeMutation,
  useGetSafeStatementQuery,

  // Journals
  useGetJournalsQuery,
  useGetJournalQuery,
  useCreateJournalMutation,
  usePostJournalMutation,
  useReverseJournalMutation,
  useCancelJournalMutation,

  // Partners
  useGetPartnersQuery,
  useGetPartnerQuery,
  useCreatePartnerMutation,
  useUpdatePartnerMutation,
  useDeletePartnerMutation,
  useDepositPartnerCapitalMutation,
  useWithdrawPartnerMutation,
  useGetPartnerStatementQuery,

  // Counterparties
  useGetCounterpartiesQuery,
  useGetCounterpartyQuery,
  useCreateCounterpartyMutation,
  useUpdateCounterpartyMutation,

  // Periods
  useGetPeriodsQuery,
  useGetPeriodQuery,
  useCreatePeriodMutation,
  useClosePeriodMutation,
  useLockPeriodMutation,
  useReopenPeriodMutation,

  // Reconciliations
  useGetReconciliationsQuery,
  useGetReconciliationQuery,
  useCreateReconciliationMutation,

  // Salary Payments
  useGetSalaryPaymentsQuery,
  useCreateSalaryPaymentMutation,
  useDeleteSalaryPaymentMutation,

  // Reports & Dashboard
  useGetTrialBalanceQuery,
  useGetIncomeStatementQuery,
  useGetBalanceSheetQuery,
  useGetTransactionsByTypeQuery,
  useGetAccountingDashboardQuery,
} = accountingApi;
