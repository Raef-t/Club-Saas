"use client";

import { useEffect, useMemo, useState } from "react";
import { useSearchParams } from "next/navigation";
import {
  useGetSalaryPaymentsQuery,
  useCreateSalaryPaymentMutation,
  useDeleteSalaryPaymentMutation,
  useGetSafesQuery,
  useGetPeriodsQuery,
} from "@/lib/api/accountingApi";
import { useGetPayslipsQuery } from "@/lib/api/payslipsApi";
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
  const searchParams = useSearchParams();
  const { selectedBranchId, branches } = useManagementBranch();

  const [search, setSearch] = useState("");
  const [periodFilter, setPeriodFilter] = useState("all");
  const [roleFilter, setRoleFilter] = useState("all");
  const [isFormOpen, setIsFormOpen] = useState(false);
  const [initialFormValues, setInitialFormValues] = useState(null);
  const [deletePayment, setDeletePayment] = useState(null);
  const [deleteConfirmation, setDeleteConfirmation] = useState("");
  const [formErrors, setFormErrors] = useState({});

  useEffect(() => {
    if (searchParams) {
      const openModal = searchParams.get("openModal");
      const payslipId = searchParams.get("payslip_id");
      const staffId = searchParams.get("staff_id");
      const amount = searchParams.get("amount");

      if (openModal === "true" || payslipId) {
        setInitialFormValues({
          payslip_id: payslipId || "",
          staff_id: staffId || "",
          amount: amount || "",
        });
        setIsFormOpen(true);
      }
    }
  }, [searchParams]);

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
  const { data: payslipsResponse } = useGetPayslipsQuery(
    selectedBranchId && selectedBranchId !== "all"
      ? { branch_id: selectedBranchId, status: "pending" }
      : { status: "pending" }
  );

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

  const unpaidPayslips = useMemo(() => {
    const raw = payslipsResponse?.data?.data || payslipsResponse?.data || payslipsResponse || [];
    return Array.isArray(raw) ? raw.filter((p) => p.status !== "paid") : [];
  }, [payslipsResponse]);

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
    if (!deletePayment?.id || deleteConfirmation !== "delete") return;
    try {
      await deletePaymentMutation(deletePayment.id).unwrap();
      toast.success("تم حذف سجل صرف الراتب بنجاح");
      setDeletePayment(null);
      setDeleteConfirmation("");
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
    unpaidPayslips,
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
    deleteConfirmation,
    setDeleteConfirmation,
    formErrors,
    isSaving: isCreating,
    isDeleting,
    openCreateModal,
    closeFormModal,
    handleSavePayment,
    handleDeletePayment,
    initialFormValues,
  };
}
