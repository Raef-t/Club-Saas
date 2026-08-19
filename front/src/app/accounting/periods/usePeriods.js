"use client";

import { useMemo, useState } from "react";
import {
  useGetPeriodsQuery,
  useCreatePeriodMutation,
  useClosePeriodMutation,
  useLockPeriodMutation,
  useReopenPeriodMutation,
} from "@/lib/api/accountingApi";
import { useToast } from "@/components/ui/Toast";
import { getApiErrorMessage, getApiFieldErrors } from "@/lib/apiError";

export const PERIOD_STATUSES = {
  open: { label: "مفتوحة (Open)", color: "text-emerald-400 bg-emerald-500/10 border-emerald-500/20" },
  closed: { label: "مغلقة (Closed)", color: "text-amber-400 bg-amber-500/10 border-amber-500/20" },
  locked: { label: "مقفلة نهائياً (Locked)", color: "text-rose-400 bg-rose-500/10 border-rose-500/20" },
};

export function usePeriods({ initialPeriods = [] } = {}) {
  const toast = useToast();
  const [search, setSearch] = useState("");
  const [isFormOpen, setIsFormOpen] = useState(false);
  const [formErrors, setFormErrors] = useState({});

  const { data: periodsResponse, isLoading, isFetching, refetch } = useGetPeriodsQuery();
  const [createPeriodMutation, { isLoading: isCreating }] = useCreatePeriodMutation();
  const [closePeriodMutation, { isLoading: isClosing }] = useClosePeriodMutation();
  const [lockPeriodMutation, { isLoading: isLocking }] = useLockPeriodMutation();
  const [reopenPeriodMutation, { isLoading: isReopening }] = useReopenPeriodMutation();

  const periods = useMemo(() => {
    const raw = periodsResponse?.data || initialPeriods;
    return Array.isArray(raw) ? raw : [];
  }, [periodsResponse, initialPeriods]);

  const filteredPeriods = useMemo(() => {
    return periods.filter((p) => {
      return (
        !search.trim() ||
        p.name?.toLowerCase().includes(search.toLowerCase())
      );
    });
  }, [periods, search]);

  const stats = useMemo(() => {
    const total = periods.length;
    const open = periods.filter((p) => p.status === "open").length;
    const closed = periods.filter((p) => p.status === "closed").length;
    const locked = periods.filter((p) => p.status === "locked").length;
    return { total, open, closed, locked };
  }, [periods]);

  const openCreateModal = () => {
    setFormErrors({});
    setIsFormOpen(true);
  };

  const closeFormModal = () => {
    setIsFormOpen(false);
    setFormErrors({});
  };

  const handleSavePeriod = async (formData) => {
    setFormErrors({});
    try {
      await createPeriodMutation(formData).unwrap();
      toast.success("تم إنشاء الفترة المالية بنجاح");
      closeFormModal();
      return true;
    } catch (err) {
      const fieldErrors = getApiFieldErrors(err);
      if (Object.keys(fieldErrors).length > 0) {
        setFormErrors(fieldErrors);
      }
      toast.error(getApiErrorMessage(err, "فشل إنشاء الفترة المالية"));
      return false;
    }
  };

  const handleClosePeriod = async (id) => {
    try {
      await closePeriodMutation(id).unwrap();
      toast.success("تم إغلاق الفترة المالية بنجاح");
      return true;
    } catch (err) {
      toast.error(getApiErrorMessage(err, "تعذر إغلاق الفترة المالية"));
      return false;
    }
  };

  const handleLockPeriod = async (id) => {
    try {
      await lockPeriodMutation(id).unwrap();
      toast.success("تم قفل الفترة المالية نهائياً ومنع التعديل");
      return true;
    } catch (err) {
      toast.error(getApiErrorMessage(err, "تعذر قفل الفترة المالية"));
      return false;
    }
  };

  const handleReopenPeriod = async (id) => {
    try {
      await reopenPeriodMutation(id).unwrap();
      toast.success("تمت إعادة فتح الفترة المالية بنجاح");
      return true;
    } catch (err) {
      toast.error(getApiErrorMessage(err, "تعذر إعادة فتح الفترة المالية"));
      return false;
    }
  };

  return {
    periods,
    filteredPeriods,
    stats,
    search,
    setSearch,
    isLoading: isLoading && periods.length === 0,
    isFetching,
    refetch,
    isFormOpen,
    formErrors,
    isSaving: isCreating,
    isProcessing: isClosing || isLocking || isReopening,
    openCreateModal,
    closeFormModal,
    handleSavePeriod,
    handleClosePeriod,
    handleLockPeriod,
    handleReopenPeriod,
  };
}
