"use client";

import { useMemo, useState } from "react";
import {
  useGetSafesQuery,
  useCreateSafeMutation,
  useUpdateSafeMutation,
  useCreateReconciliationMutation,
  useGetAccountsQuery,
} from "@/lib/api/accountingApi";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { useToast } from "@/components/ui/Toast";
import { getApiErrorMessage, getApiFieldErrors } from "@/lib/apiError";

export function useSafes({ initialSafes = [], initialAccounts = [] } = {}) {
  const toast = useToast();
  const { selectedBranchId, branches } = useManagementBranch();
  const [search, setSearch] = useState("");
  const [currencyFilter, setCurrencyFilter] = useState("all");
  const [isFormOpen, setIsFormOpen] = useState(false);
  const [editingSafe, setEditingSafe] = useState(null);
  const [statementSafe, setStatementSafe] = useState(null);
  const [reconciliationSafe, setReconciliationSafe] = useState(null);
  const [formErrors, setFormErrors] = useState({});

  const queryParams = useMemo(() => {
    return selectedBranchId && selectedBranchId !== "all"
      ? { branch_id: selectedBranchId }
      : {};
  }, [selectedBranchId]);

  const { data: safesResponse, isLoading, isFetching, refetch } = useGetSafesQuery(queryParams);
  const { data: accountsResponse } = useGetAccountsQuery();

  const [createSafeMutation, { isLoading: isCreating }] = useCreateSafeMutation();
  const [updateSafeMutation, { isLoading: isUpdating }] = useUpdateSafeMutation();
  const [createReconciliationMutation, { isLoading: isReconciling }] = useCreateReconciliationMutation();

  const safes = useMemo(() => {
    const raw = safesResponse?.data?.data || safesResponse?.data || initialSafes?.data || initialSafes;
    return Array.isArray(raw) ? raw : [];
  }, [safesResponse, initialSafes]);

  const accounts = useMemo(() => {
    const raw = accountsResponse?.data?.data || accountsResponse?.data || initialAccounts?.data || initialAccounts;
    return Array.isArray(raw) ? raw : [];
  }, [accountsResponse, initialAccounts]);

  const safeBranches = useMemo(() => {
    return Array.isArray(branches) ? branches : [];
  }, [branches]);

  const filteredSafes = useMemo(() => {
    return safes.filter((safe) => {
      const matchSearch =
        !search.trim() ||
        safe.name?.toLowerCase().includes(search.toLowerCase()) ||
        safe.notes?.toLowerCase().includes(search.toLowerCase());

      const matchCurrency = currencyFilter === "all" || safe.currency === currencyFilter;

      return matchSearch && matchCurrency;
    });
  }, [safes, search, currencyFilter]);


  const stats = useMemo(() => {
    const total = safes.length;
    const active = safes.filter((s) => s.is_active).length;
    const usdSafes = safes.filter((s) => s.currency === "USD").length;
    const sypSafes = safes.filter((s) => s.currency === "SYP").length;

    return { total, active, usdSafes, sypSafes };
  }, [safes]);

  const openCreateModal = () => {
    setEditingSafe(null);
    setFormErrors({});
    setIsFormOpen(true);
  };

  const openEditModal = (safe) => {
    setEditingSafe(safe);
    setFormErrors({});
    setIsFormOpen(true);
  };

  const closeFormModal = () => {
    setIsFormOpen(false);
    setEditingSafe(null);
    setFormErrors({});
  };

  const openStatementModal = (safe) => {
    setStatementSafe(safe);
  };

  const closeStatementModal = () => {
    setStatementSafe(null);
  };

  const openReconciliationModal = (safe) => {
    setReconciliationSafe(safe);
  };

  const closeReconciliationModal = () => {
    setReconciliationSafe(null);
  };

  const handleSaveSafe = async (formData) => {
    setFormErrors({});
    try {
      if (editingSafe?.id) {
        await updateSafeMutation({ id: editingSafe.id, body: formData }).unwrap();
        toast.success("تم تحديث بيانات الصندوق بنجاح");
      } else {
        await createSafeMutation(formData).unwrap();
        toast.success("تم إنشاء الصندوق المالي وربطه بالفرع بنجاح");
      }
      closeFormModal();
      return true;
    } catch (err) {
      const fieldErrors = getApiFieldErrors(err);
      if (Object.keys(fieldErrors).length > 0) {
        setFormErrors(fieldErrors);
      }
      toast.error(getApiErrorMessage(err, "فشل حفظ الصندوق المالي"));
      return false;
    }
  };

  const handleSaveReconciliation = async (formData) => {
    try {
      await createReconciliationMutation(formData).unwrap();
      toast.success("تم تسجيل ومطابقة تسوية الصندوق بنجاح");
      closeReconciliationModal();
      return true;
    } catch (err) {
      toast.error(getApiErrorMessage(err, "فشل حفظ تسوية الصندوق"));
      return false;
    }
  };

  return {
    safes,
    filteredSafes,
    accounts,
    branches: safeBranches,
    selectedBranchId,

    stats,
    search,
    setSearch,
    currencyFilter,
    setCurrencyFilter,
    isLoading: isLoading && safes.length === 0,
    isFetching,
    refetch,
    isFormOpen,
    editingSafe,
    statementSafe,
    reconciliationSafe,
    formErrors,
    isSaving: isCreating || isUpdating,
    isReconciling,
    openCreateModal,
    openEditModal,
    closeFormModal,
    openStatementModal,
    closeStatementModal,
    openReconciliationModal,
    closeReconciliationModal,
    handleSaveSafe,
    handleSaveReconciliation,
  };
}
