"use client";

import { useMemo, useState } from "react";
import {
  useGetPartnersQuery,
  useCreatePartnerMutation,
  useUpdatePartnerMutation,
  useDeletePartnerMutation,
  useDepositPartnerCapitalMutation,
  useWithdrawPartnerMutation,
  useGetSafesQuery,
} from "@/lib/api/accountingApi";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { useToast } from "@/components/ui/Toast";
import { getApiErrorMessage, getApiFieldErrors } from "@/lib/apiError";

export function usePartners({ initialPartners = [], initialSafes = [] } = {}) {
  const toast = useToast();
  const { selectedBranchId, branches } = useManagementBranch();

  const [search, setSearch] = useState("");
  const [isFormOpen, setIsFormOpen] = useState(false);
  const [editingPartner, setEditingPartner] = useState(null);
  const [statementPartner, setStatementPartner] = useState(null);
  const [transactionConfig, setTransactionConfig] = useState({ isOpen: false, type: null, partner: null });
  const [deleteConfirmPartner, setDeleteConfirmPartner] = useState(null);
  const [deleteConfirmation, setDeleteConfirmation] = useState("");
  const [formErrors, setFormErrors] = useState({});

  const queryParams = useMemo(() => {
    return selectedBranchId && selectedBranchId !== "all"
      ? { branch_id: selectedBranchId }
      : {};
  }, [selectedBranchId]);

  const { data: partnersResponse, isLoading, isFetching, refetch } = useGetPartnersQuery(queryParams);
  const { data: safesResponse } = useGetSafesQuery(queryParams);

  const [createPartnerMutation, { isLoading: isCreating }] = useCreatePartnerMutation();
  const [updatePartnerMutation, { isLoading: isUpdating }] = useUpdatePartnerMutation();
  const [deletePartnerMutation, { isLoading: isDeleting }] = useDeletePartnerMutation();
  const [depositMutation, { isLoading: isDepositing }] = useDepositPartnerCapitalMutation();
  const [withdrawMutation, { isLoading: isWithdrawing }] = useWithdrawPartnerMutation();

  const partners = useMemo(() => {
    const raw = partnersResponse?.data || initialPartners;
    return Array.isArray(raw) ? raw : [];
  }, [partnersResponse, initialPartners]);

  const safes = useMemo(() => {
    const raw = safesResponse?.data || initialSafes;
    return Array.isArray(raw) ? raw : [];
  }, [safesResponse, initialSafes]);

  const filteredPartners = useMemo(() => {
    return partners.filter((p) => {
      return (
        !search.trim() ||
        p.name?.toLowerCase().includes(search.toLowerCase()) ||
        p.notes?.toLowerCase().includes(search.toLowerCase())
      );
    });
  }, [partners, search]);

  const totalActiveShare = useMemo(() => {
    return partners
      .filter((p) => p.is_active)
      .reduce((sum, p) => sum + Number(p.profit_share_pct || 0), 0);
  }, [partners]);

  const remainingAvailableShare = Math.max(0, 100 - totalActiveShare);

  const stats = useMemo(() => {
    const total = partners.length;
    const active = partners.filter((p) => p.is_active).length;
    return { total, active, totalActiveShare, remainingAvailableShare };
  }, [partners, totalActiveShare, remainingAvailableShare]);

  const openCreateModal = () => {
    setEditingPartner(null);
    setFormErrors({});
    setIsFormOpen(true);
  };

  const openEditModal = (partner) => {
    setEditingPartner(partner);
    setFormErrors({});
    setIsFormOpen(true);
  };

  const closeFormModal = () => {
    setIsFormOpen(false);
    setEditingPartner(null);
    setFormErrors({});
  };

  const openStatementModal = (partner) => {
    setStatementPartner(partner);
  };

  const closeStatementModal = () => {
    setStatementPartner(null);
  };

  const openTransactionModal = (type, partner) => {
    setTransactionConfig({ isOpen: true, type, partner });
  };

  const closeTransactionModal = () => {
    setTransactionConfig({ isOpen: false, type: null, partner: null });
  };

  const handleSavePartner = async (formData) => {
    setFormErrors({});
    try {
      if (editingPartner?.id) {
        await updatePartnerMutation({ id: editingPartner.id, body: formData }).unwrap();
        toast.success("تم تحديث بيانات الشريك بنجاح");
      } else {
        await createPartnerMutation(formData).unwrap();
        toast.success("تم تسجيل الشريك وتوليد حسابات رأس المال والمسحوبات تلقائياً");
      }
      closeFormModal();
      return true;
    } catch (err) {
      const fieldErrors = getApiFieldErrors(err);
      if (Object.keys(fieldErrors).length > 0) {
        setFormErrors(fieldErrors);
      }
      toast.error(getApiErrorMessage(err, "فشل حفظ بيانات الشريك"));
      return false;
    }
  };

  const handleDeletePartner = async () => {
    if (!deleteConfirmPartner?.id || deleteConfirmation !== "delete") return;
    try {
      await deletePartnerMutation(deleteConfirmPartner.id).unwrap();
      toast.success("تم حذف الشريك وحساباته المالية بنجاح");
      setDeleteConfirmPartner(null);
      setDeleteConfirmation("");
      return true;
    } catch (err) {
      toast.error(getApiErrorMessage(err, "تعذر حذف الشريك لوجود حركات مالية مسجلة على حساباته"));
      return false;
    }
  };

  const handleExecuteTransaction = async (formData) => {
    const { type, partner } = transactionConfig;
    if (!partner?.id) return;

    try {
      const payload = { ...formData, partner_id: partner.id };
      if (type === "deposit") {
        await depositMutation(payload).unwrap();
        toast.success("تم تسجيل إيداع رأس المال وتأثيره على الصندوق بنجاح");
      } else {
        await withdrawMutation(payload).unwrap();
        toast.success("تم تسجيل مسحوبات الشريك الشخصية والصرف من الصندوق بنجاح");
      }
      closeTransactionModal();
      return true;
    } catch (err) {
      toast.error(getApiErrorMessage(err, "فشل تسجيل الحركة المالية للشريك"));
      return false;
    }
  };

  return {
    partners,
    filteredPartners,
    safes,
    branches,
    selectedBranchId,
    stats,
    search,
    setSearch,
    isLoading: isLoading && partners.length === 0,
    isFetching,
    refetch,
    isFormOpen,
    editingPartner,
    statementPartner,
    transactionConfig,
    deleteConfirmPartner,
    setDeleteConfirmPartner,
    deleteConfirmation,
    setDeleteConfirmation,
    formErrors,
    isSaving: isCreating || isUpdating,
    isProcessingTx: isDepositing || isWithdrawing,
    isDeleting,
    openCreateModal,
    openEditModal,
    closeFormModal,
    openStatementModal,
    closeStatementModal,
    openTransactionModal,
    closeTransactionModal,
    handleSavePartner,
    handleDeletePartner,
    handleExecuteTransaction,
  };
}
