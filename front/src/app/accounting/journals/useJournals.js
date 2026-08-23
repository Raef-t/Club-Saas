"use client";

import { useMemo, useState } from "react";
import {
  useGetJournalsQuery,
  useCreateJournalMutation,
  usePostJournalMutation,
  useReverseJournalMutation,
  useCancelJournalMutation,
  useGetAccountsQuery,
  useGetSafesQuery,
  useGetCounterpartiesQuery,
} from "@/lib/api/accountingApi";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { useToast } from "@/components/ui/Toast";
import { getApiErrorMessage, getApiFieldErrors } from "@/lib/apiError";

export const JOURNAL_TYPES = {
  JV: { label: "قيد عام (JV)", badge: "bg-blue-500/10 text-blue-400 border-blue-500/20" },
  RV: { label: "سند قبض / وارد (RV)", badge: "bg-emerald-500/10 text-emerald-400 border-emerald-500/20" },
  PV: { label: "سند صرف / صادر (PV)", badge: "bg-rose-500/10 text-rose-400 border-rose-500/20" },
};

export const JOURNAL_STATUSES = {
  draft: { label: "مسودة (Draft)", color: "bg-amber-500/10 text-amber-400 border-amber-500/20" },
  posted: { label: "مرحل ومؤثر (Posted)", color: "bg-emerald-500/10 text-emerald-400 border-emerald-500/20" },
  reversed: { label: "معكوس (Reversed)", color: "bg-purple-500/10 text-purple-400 border-purple-500/20" },
  cancelled: { label: "ملغي (Cancelled)", color: "bg-rose-500/10 text-rose-400 border-rose-500/20" },
};

export function useJournals({
  initialJournals = [],
  initialAccounts = [],
  initialSafes = [],
  initialCounterparties = [],
  defaultType = null,
} = {}) {
  const toast = useToast();
  const { selectedBranchId, branches } = useManagementBranch();

  const [typeFilter, setTypeFilter] = useState(defaultType || "all");
  const [statusFilter, setStatusFilter] = useState("all");
  const [fromDate, setFromDate] = useState("");
  const [toDate, setToDate] = useState("");
  const [search, setSearch] = useState("");
  const [page, setPage] = useState(1);

  const [isFormOpen, setIsFormOpen] = useState(false);
  const [selectedJournal, setSelectedJournal] = useState(null);
  const [actionConfig, setActionConfig] = useState({ isOpen: false, type: null, journal: null });
  const [formErrors, setFormErrors] = useState({});

  const queryParams = useMemo(() => {
    const params = { page, per_page: 25 };
    if (typeFilter && typeFilter !== "all") params.type = typeFilter;
    if (statusFilter && statusFilter !== "all") params.status = statusFilter;
    if (fromDate) params.from_date = fromDate;
    if (toDate) params.to_date = toDate;
    if (selectedBranchId && selectedBranchId !== "all") params.branch_id = selectedBranchId;
    return params;
  }, [typeFilter, statusFilter, fromDate, toDate, selectedBranchId, page]);

  const { data: journalsResponse, isLoading, isFetching, refetch } = useGetJournalsQuery(queryParams);
  const { data: accountsResponse } = useGetAccountsQuery();
  const { data: safesResponse } = useGetSafesQuery(
    selectedBranchId && selectedBranchId !== "all" ? { branch_id: selectedBranchId } : {}
  );
  const { data: counterpartiesResponse } = useGetCounterpartiesQuery();

  const [createJournalMutation, { isLoading: isCreating }] = useCreateJournalMutation();
  const [postJournalMutation, { isLoading: isPosting }] = usePostJournalMutation();
  const [reverseJournalMutation, { isLoading: isReversing }] = useReverseJournalMutation();
  const [cancelJournalMutation, { isLoading: isCancelling }] = useCancelJournalMutation();

  const journalsData = journalsResponse?.data || initialJournals;
  const journals = useMemo(() => {
    const list = Array.isArray(journalsData?.data) ? journalsData.data : Array.isArray(journalsData) ? journalsData : [];
    if (!search.trim()) return list;

    const q = search.toLowerCase().trim();

    return list.filter((j) => {
      const numMatch = (j.number || "").toLowerCase().includes(q) || (j.reference_number || "").toLowerCase().includes(q);
      const descMatch = (j.description || "").toLowerCase().includes(q);
      const notesMatch = (j.notes || "").toLowerCase().includes(q);
      const safeMatch = (j.safe?.name || "").toLowerCase().includes(q);
      const cpMatch = (j.counterparty?.name || "").toLowerCase().includes(q);
      const entriesMatch = (j.entries || []).some((e) =>
        (e.memo || "").toLowerCase().includes(q) ||
        (e.account?.name || "").toLowerCase().includes(q) ||
        (e.account?.code || "").toLowerCase().includes(q) ||
        String(e.debit_usd || "").includes(q) ||
        String(e.credit_usd || "").includes(q) ||
        String(e.debit_syp || "").includes(q) ||
        String(e.credit_syp || "").includes(q)
      );

      return numMatch || descMatch || notesMatch || safeMatch || cpMatch || entriesMatch;
    });
  }, [journalsData, search]);

  const pagination = useMemo(() => {
    if (journalsData?.current_page) {
      return {
        currentPage: journalsData.current_page,
        lastPage: journalsData.last_page || 1,
        total: journalsData.total || journals.length,
        perPage: journalsData.per_page || 25,
      };
    }
    return { currentPage: 1, lastPage: 1, total: journals.length, perPage: 25 };
  }, [journalsData, journals.length]);

  const accounts = useMemo(() => {
    const raw = accountsResponse?.data || initialAccounts;
    return Array.isArray(raw) ? raw : [];
  }, [accountsResponse, initialAccounts]);

  const safes = useMemo(() => {
    const raw = safesResponse?.data || initialSafes;
    return Array.isArray(raw) ? raw : [];
  }, [safesResponse, initialSafes]);

  const counterparties = useMemo(() => {
    const raw = counterpartiesResponse?.data || initialCounterparties;
    return Array.isArray(raw) ? raw : [];
  }, [counterpartiesResponse, initialCounterparties]);

  const openCreateModal = () => {
    setFormErrors({});
    setIsFormOpen(true);
  };

  const closeFormModal = () => {
    setIsFormOpen(false);
    setFormErrors({});
  };

  const openDetailsModal = (journal) => {
    setSelectedJournal(journal);
  };

  const closeDetailsModal = () => {
    setSelectedJournal(null);
  };

  const openActionModal = (type, journal) => {
    setActionConfig({ isOpen: true, type, journal });
  };

  const closeActionModal = () => {
    setActionConfig({ isOpen: false, type: null, journal: null });
  };

  const handleSaveJournal = async (formData) => {
    setFormErrors({});
    try {
      await createJournalMutation(formData).unwrap();
      toast.success("تم تسجيل سند القيد اليومي بنجاح");
      closeFormModal();
      return true;
    } catch (err) {
      const fieldErrors = getApiFieldErrors(err);
      if (Object.keys(fieldErrors).length > 0) {
        setFormErrors(fieldErrors);
      }
      toast.error(getApiErrorMessage(err, "فشل إنشاء سند القيد"));
      return false;
    }
  };

  const handleExecuteAction = async (reason = "") => {
    const { type, journal } = actionConfig;
    if (!journal?.id) return;

    try {
      if (type === "post") {
        await postJournalMutation(journal.id).unwrap();
        toast.success("تم ترحيل السند بنجاح والتأثير على الحسابات");
      } else if (type === "reverse") {
        await reverseJournalMutation({ id: journal.id, body: { reason } }).unwrap();
        toast.success("تم إنشاء قيد عكسي بنجاح");
      } else if (type === "cancel") {
        await cancelJournalMutation({ id: journal.id, body: { cancellation_reason: reason } }).unwrap();
        toast.success("تم إلغاء السند المالي بنجاح");
      }
      closeActionModal();
      if (selectedJournal?.id === journal.id) {
        closeDetailsModal();
      }
      return true;
    } catch (err) {
      toast.error(getApiErrorMessage(err, "فشل تنفيذ العملية على السند"));
      return false;
    }
  };

  return {
    journals,
    pagination,
    accounts,
    safes,
    counterparties,
    branches,
    selectedBranchId,
    typeFilter,
    setTypeFilter,
    statusFilter,
    setStatusFilter,
    fromDate,
    setFromDate,
    toDate,
    setToDate,
    search,
    setSearch,
    page,
    setPage,
    isLoading: isLoading && journals.length === 0,
    isFetching,
    refetch,
    isFormOpen,
    selectedJournal,
    actionConfig,
    formErrors,
    isSaving: isCreating,
    isProcessingAction: isPosting || isReversing || isCancelling,
    openCreateModal,
    closeFormModal,
    openDetailsModal,
    closeDetailsModal,
    openActionModal,
    closeActionModal,
    handleSaveJournal,
    handleExecuteAction,
  };
}
