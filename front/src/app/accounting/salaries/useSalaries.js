"use client";

import { useMemo, useState } from "react";
import {
  useGetSalaryPaymentsQuery,
  useCreateSalaryPaymentMutation,
  useDeleteSalaryPaymentMutation,
  useGetSafesQuery,
  useGetPeriodsQuery,
} from "@/lib/api/accountingApi";
import { useGetStaffQuery } from "@/lib/api/staffApi";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { useToast } from "@/components/ui/Toast";
import { getApiErrorMessage, getApiFieldErrors } from "@/lib/apiError";

export function useSalaries({
  initialPayments = [],
  initialStaff = [],
  initialSafes = [],
  initialPeriods = [],
} = {}) {
  const toast = useToast();
  const { selectedBranchId, branches } = useManagementBranch();

  const [search, setSearch] = useState("");
  const [periodFilter, setPeriodFilter] = useState("all");
  const [roleFilter, setRoleFilter] = useState("all");
  const [isFormOpen, setIsFormOpen] = useState(false);
  const [deletePayment, setDeletePayment] = useState(null);
  const [formErrors, setFormErrors] = useState({});

  const queryParams = useMemo(() => {
    const params = {};
    if (selectedBranchId && selectedBranchId !== "all") params.branch_id = selectedBranchId;
    if (periodFilter && periodFilter !== "all") params.period_id = periodFilter;
    if (search.trim()) params.search = search.trim();
    return params;
  }, [selectedBranchId, periodFilter, search]);

  const { data: paymentsResponse, isLoading, isFetching, refetch } = useGetSalaryPaymentsQuery(queryParams);
  const { data: staffResponse } = useGetStaffQuery(
    selectedBranchId && selectedBranchId !== "all" ? { branch_id: selectedBranchId } : {}
  );
  const { data: safesResponse } = useGetSafesQuery(
    selectedBranchId && selectedBranchId !== "all" ? { branch_id: selectedBranchId } : {}
  );
  const { data: periodsResponse } = useGetPeriodsQuery();

  const [createPaymentMutation, { isLoading: isCreating }] = useCreateSalaryPaymentMutation();
  const [deletePaymentMutation, { isLoading: isDeleting }] = useDeleteSalaryPaymentMutation();

  const paymentsData = paymentsResponse?.data || initialPayments;
  const payments = useMemo(() => {
    const raw = Array.isArray(paymentsData?.data) ? paymentsData.data : Array.isArray(paymentsData) ? paymentsData : [];
    if (roleFilter === "all") return raw;
    return raw.filter((p) => p.staff?.role === roleFilter);
  }, [paymentsData, roleFilter]);

  const staffList = useMemo(() => {
    const raw = staffResponse?.data?.data || staffResponse?.data || initialStaff;
    return Array.isArray(raw) ? raw : [];
  }, [staffResponse, initialStaff]);

  const safes = useMemo(() => {
    const raw = safesResponse?.data || initialSafes;
    return Array.isArray(raw) ? raw : [];
  }, [safesResponse, initialSafes]);

  const periods = useMemo(() => {
    const raw = periodsResponse?.data || initialPeriods;
    return Array.isArray(raw) ? raw : [];
  }, [periodsResponse, initialPeriods]);

  const stats = useMemo(() => {
    const totalCount = payments.length;
    const totalAmount = payments.reduce((sum, p) => sum + Number(p.amount || 0), 0);
    const trainerCount = payments.filter((p) => p.staff?.role === "coach" || p.staff?.role === "trainer").length;
    const staffCount = totalCount - trainerCount;

    return { totalCount, totalAmount, trainerCount, staffCount };
  }, [payments]);

  const openCreateModal = () => {
    setFormErrors({});
    setIsFormOpen(true);
  };

  const closeFormModal = () => {
    setIsFormOpen(false);
    setFormErrors({});
  };

  const handleSavePayment = async (formData) => {
    setFormErrors({});
    try {
      await createPaymentMutation(formData).unwrap();
      toast.success("تم صرف الراتب وتسجيل سند الصرف والتأثير على الصندوق بنجاح");
      closeFormModal();
      return true;
    } catch (err) {
      const fieldErrors = getApiFieldErrors(err);
      if (Object.keys(fieldErrors).length > 0) {
        setFormErrors(fieldErrors);
      }
      toast.error(getApiErrorMessage(err, "فشل تسجيل صرف الراتب"));
      return false;
    }
  };

  const handleDeletePayment = async () => {
    if (!deletePayment?.id) return;
    try {
      await deletePaymentMutation(deletePayment.id).unwrap();
      toast.success("تم حذف سجل صرف الراتب بنجاح");
      setDeletePayment(null);
      return true;
    } catch (err) {
      toast.error(getApiErrorMessage(err, "تعذر حذف سجل صرف الراتب"));
      return false;
    }
  };

  return {
    payments,
    staffList,
    safes,
    periods,
    branches,
    selectedBranchId,
    stats,
    search,
    setSearch,
    periodFilter,
    setPeriodFilter,
    roleFilter,
    setRoleFilter,
    isLoading: isLoading && payments.length === 0,
    isFetching,
    refetch,
    isFormOpen,
    deletePayment,
    setDeletePayment,
    formErrors,
    isSaving: isCreating,
    isDeleting,
    openCreateModal,
    closeFormModal,
    handleSavePayment,
    handleDeletePayment,
  };
}
