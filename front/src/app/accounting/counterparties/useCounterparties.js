"use client";

import { useMemo, useState } from "react";
import {
  useGetCounterpartiesQuery,
  useCreateCounterpartyMutation,
  useUpdateCounterpartyMutation,
} from "@/lib/api/accountingApi";
import { useToast } from "@/components/ui/Toast";
import { getApiErrorMessage, getApiFieldErrors } from "@/lib/apiError";

export const COUNTERPARTY_TYPES = {
  supplier: { label: "مورد (Supplier)", color: "text-amber-400 bg-amber-500/10 border-amber-500/20" },
  customer: { label: "عميل (Customer)", color: "text-emerald-400 bg-emerald-500/10 border-emerald-500/20" },
  other: { label: "جهة أخرى (Other)", color: "text-blue-400 bg-blue-500/10 border-blue-500/20" },
};

export function useCounterparties({ initialCounterparties = [] } = {}) {
  const toast = useToast();
  const [search, setSearch] = useState("");
  const [typeFilter, setTypeFilter] = useState("all");
  const [isFormOpen, setIsFormOpen] = useState(false);
  const [editingCounterparty, setEditingCounterparty] = useState(null);
  const [formErrors, setFormErrors] = useState({});

  const { data: counterpartiesResponse, isLoading, isFetching, refetch } = useGetCounterpartiesQuery();
  const [createCounterpartyMutation, { isLoading: isCreating }] = useCreateCounterpartyMutation();
  const [updateCounterpartyMutation, { isLoading: isUpdating }] = useUpdateCounterpartyMutation();

  const counterparties = useMemo(() => {
    const raw = counterpartiesResponse?.data || initialCounterparties;
    return Array.isArray(raw) ? raw : [];
  }, [counterpartiesResponse, initialCounterparties]);

  const filteredCounterparties = useMemo(() => {
    return counterparties.filter((c) => {
      const matchSearch =
        !search.trim() ||
        c.name?.toLowerCase().includes(search.toLowerCase()) ||
        c.phone?.toLowerCase().includes(search.toLowerCase()) ||
        c.notes?.toLowerCase().includes(search.toLowerCase());

      const matchType = typeFilter === "all" || c.type === typeFilter;

      return matchSearch && matchType;
    });
  }, [counterparties, search, typeFilter]);

  const stats = useMemo(() => {
    const total = counterparties.length;
    const suppliers = counterparties.filter((c) => c.type === "supplier").length;
    const customers = counterparties.filter((c) => c.type === "customer").length;
    const others = counterparties.filter((c) => c.type === "other").length;

    return { total, suppliers, customers, others };
  }, [counterparties]);

  const openCreateModal = () => {
    setEditingCounterparty(null);
    setFormErrors({});
    setIsFormOpen(true);
  };

  const openEditModal = (counterparty) => {
    setEditingCounterparty(counterparty);
    setFormErrors({});
    setIsFormOpen(true);
  };

  const closeFormModal = () => {
    setIsFormOpen(false);
    setEditingCounterparty(null);
    setFormErrors({});
  };

  const handleSaveCounterparty = async (formData) => {
    setFormErrors({});
    try {
      if (editingCounterparty?.id) {
        await updateCounterpartyMutation({ id: editingCounterparty.id, body: formData }).unwrap();
        toast.success("تم تحديث بيانات الطرف بنجاح");
      } else {
        await createCounterpartyMutation(formData).unwrap();
        toast.success("تم تسجيل الطرف الجديد بنجاح");
      }
      closeFormModal();
      return true;
    } catch (err) {
      const fieldErrors = getApiFieldErrors(err);
      if (Object.keys(fieldErrors).length > 0) {
        setFormErrors(fieldErrors);
      }
      toast.error(getApiErrorMessage(err, "فشل حفظ بيانات الطرف"));
      return false;
    }
  };

  return {
    counterparties,
    filteredCounterparties,
    stats,
    search,
    setSearch,
    typeFilter,
    setTypeFilter,
    isLoading: isLoading && counterparties.length === 0,
    isFetching,
    refetch,
    isFormOpen,
    editingCounterparty,
    formErrors,
    isSaving: isCreating || isUpdating,
    openCreateModal,
    openEditModal,
    closeFormModal,
    handleSaveCounterparty,
  };
}
