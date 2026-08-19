"use client";

import { useMemo, useState } from "react";
import {
  useGetPeriodsQuery,
  useGetTrialBalanceQuery,
  useGetIncomeStatementQuery,
  useGetBalanceSheetQuery,
} from "@/lib/api/accountingApi";
import { useManagementBranch } from "@/lib/ManagementBranchContext";

export function useFinancialReports({ initialPeriods = [] } = {}) {
  const { selectedBranchId, branches } = useManagementBranch();
  const [activeTab, setActiveTab] = useState("trial-balance");
  const [selectedPeriodId, setSelectedPeriodId] = useState("");

  const { data: periodsResponse, isLoading: isLoadingPeriods } = useGetPeriodsQuery();

  const periods = useMemo(() => {
    const raw = periodsResponse?.data || initialPeriods;
    return Array.isArray(raw) ? raw : [];
  }, [periodsResponse, initialPeriods]);

  // Set default period if not selected
  const activePeriodId = useMemo(() => {
    if (selectedPeriodId) return selectedPeriodId;
    if (periods.length > 0) return String(periods[0].id);
    return "";
  }, [selectedPeriodId, periods]);

  const queryParams = useMemo(() => {
    if (!activePeriodId) return null;
    const params = { period_id: Number(activePeriodId) };
    if (selectedBranchId && selectedBranchId !== "all") {
      params.branch_id = Number(selectedBranchId);
    }
    return params;
  }, [activePeriodId, selectedBranchId]);

  const {
    data: trialBalanceResponse,
    isLoading: isLoadingTrialBalance,
    isFetching: isFetchingTrialBalance,
  } = useGetTrialBalanceQuery(queryParams, { skip: !queryParams || activeTab !== "trial-balance" });

  const {
    data: incomeStatementResponse,
    isLoading: isLoadingIncomeStatement,
    isFetching: isFetchingIncomeStatement,
  } = useGetIncomeStatementQuery(queryParams, { skip: !queryParams || activeTab !== "income-statement" });

  const {
    data: balanceSheetResponse,
    isLoading: isLoadingBalanceSheet,
    isFetching: isFetchingBalanceSheet,
  } = useGetBalanceSheetQuery(queryParams, { skip: !queryParams || activeTab !== "balance-sheet" });

  const trialBalance = trialBalanceResponse?.data || {};
  const incomeStatement = incomeStatementResponse?.data || {};
  const balanceSheet = balanceSheetResponse?.data || {};

  const isLoading =
    isLoadingPeriods ||
    (activeTab === "trial-balance" && isLoadingTrialBalance) ||
    (activeTab === "income-statement" && isLoadingIncomeStatement) ||
    (activeTab === "balance-sheet" && isLoadingBalanceSheet);

  return {
    activeTab,
    setActiveTab,
    selectedPeriodId: activePeriodId,
    setSelectedPeriodId,
    periods,
    branches,
    selectedBranchId,
    trialBalance,
    incomeStatement,
    balanceSheet,
    isLoading,
  };
}
