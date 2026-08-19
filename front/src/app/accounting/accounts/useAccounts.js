"use client";

import { useMemo, useState } from "react";
import {
  useGetAccountsQuery,
  useCreateAccountMutation,
  useUpdateAccountMutation,
} from "@/lib/api/accountingApi";
import { useToast } from "@/components/ui/Toast";
import { getApiErrorMessage, getApiFieldErrors } from "@/lib/apiError";

export const ACCOUNT_TYPES = {
  asset: { label: "أصول (Assets)", color: "text-blue-400 bg-blue-500/10 border-blue-500/20" },
  liability: { label: "خصوم (Liabilities)", color: "text-amber-400 bg-amber-500/10 border-amber-500/20" },
  equity: { label: "حقوق ملكية (Equity)", color: "text-purple-400 bg-purple-500/10 border-purple-500/20" },
  revenue: { label: "إيرادات (Revenues)", color: "text-emerald-400 bg-emerald-500/10 border-emerald-500/20" },
  expense: { label: "مصروفات (Expenses)", color: "text-rose-400 bg-rose-500/10 border-rose-500/20" },
};

export const CURRENCY_OPTIONS = [
  { value: "USD", label: "دولار أمريكي (USD)" },
  { value: "SYP", label: "ليرة سورية (SYP)" },
  { value: "BOTH", label: "العملتان (USD & SYP)" },
];

export function buildAccountTree(accounts) {
  const map = {};
  const roots = [];

  accounts.forEach((acc) => {
    map[acc.id] = { ...acc, children: [] };
  });

  accounts.forEach((acc) => {
    if (acc.parent_id && map[acc.parent_id]) {
      map[acc.parent_id].children.push(map[acc.id]);
    } else {
      roots.push(map[acc.id]);
    }
  });

  return roots;
}

export function useAccounts({ initialAccounts = [] } = {}) {
  const toast = useToast();
  const [search, setSearch] = useState("");
  const [typeFilter, setTypeFilter] = useState("all");
  const [viewMode, setViewMode] = useState("tree"); // "tree" | "list"
  const [isFormOpen, setIsFormOpen] = useState(false);
  const [editingAccount, setEditingAccount] = useState(null);
  const [ledgerAccount, setLedgerAccount] = useState(null);
  const [formErrors, setFormErrors] = useState({});

  const { data: accountsResponse, isLoading, isFetching, refetch } = useGetAccountsQuery();
  const [createAccountMutation, { isLoading: isCreating }] = useCreateAccountMutation();
  const [updateAccountMutation, { isLoading: isUpdating }] = useUpdateAccountMutation();

  const accounts = useMemo(() => {
    const raw = accountsResponse?.data || initialAccounts;
    return Array.isArray(raw) ? raw : [];
  }, [accountsResponse, initialAccounts]);

  const filteredAccounts = useMemo(() => {
    return accounts.filter((acc) => {
      const matchSearch =
        !search.trim() ||
        acc.name?.toLowerCase().includes(search.toLowerCase()) ||
        acc.code?.toLowerCase().includes(search.toLowerCase()) ||
        acc.name_en?.toLowerCase().includes(search.toLowerCase());

      const matchType = typeFilter === "all" || acc.type === typeFilter;

      return matchSearch && matchType;
    });
  }, [accounts, search, typeFilter]);

  const accountTree = useMemo(() => {
    return buildAccountTree(filteredAccounts);
  }, [filteredAccounts]);

  const stats = useMemo(() => {
    const total = accounts.length;
    const assets = accounts.filter((a) => a.type === "asset").length;
    const liabilities = accounts.filter((a) => a.type === "liability").length;
    const equity = accounts.filter((a) => a.type === "equity").length;
    const revenues = accounts.filter((a) => a.type === "revenue").length;
    const expenses = accounts.filter((a) => a.type === "expense").length;

    return { total, assets, liabilities, equity, revenues, expenses };
  }, [accounts]);

  const openCreateModal = (parent = null) => {
    setEditingAccount(parent ? { parent_id: parent.id, type: parent.type, currency: parent.currency } : null);
    setFormErrors({});
    setIsFormOpen(true);
  };

  const openEditModal = (account) => {
    setEditingAccount(account);
    setFormErrors({});
    setIsFormOpen(true);
  };

  const closeFormModal = () => {
    setIsFormOpen(false);
    setEditingAccount(null);
    setFormErrors({});
  };

  const openLedgerModal = (account) => {
    setLedgerAccount(account);
  };

  const closeLedgerModal = () => {
    setLedgerAccount(null);
  };

  const handleSaveAccount = async (formData) => {
    setFormErrors({});
    try {
      if (editingAccount?.id) {
        await updateAccountMutation({ id: editingAccount.id, body: formData }).unwrap();
        toast.success("تم تحديث بيانات الحساب بنجاح");
      } else {
        await createAccountMutation(formData).unwrap();
        toast.success("تم إنشاء الحساب بنجاح وإضافته للدليل");
      }
      closeFormModal();
      return true;
    } catch (err) {
      const fieldErrors = getApiFieldErrors(err);
      if (Object.keys(fieldErrors).length > 0) {
        setFormErrors(fieldErrors);
      }
      toast.error(getApiErrorMessage(err, "فشل حفظ الحساب المحاسبي"));
      return false;
    }
  };

  return {
    accounts,
    filteredAccounts,
    accountTree,
    stats,
    search,
    setSearch,
    typeFilter,
    setTypeFilter,
    viewMode,
    setViewMode,
    isLoading: isLoading && accounts.length === 0,
    isFetching,
    refetch,
    isFormOpen,
    editingAccount,
    formErrors,
    isSaving: isCreating || isUpdating,
    ledgerAccount,
    openCreateModal,
    openEditModal,
    closeFormModal,
    openLedgerModal,
    closeLedgerModal,
    handleSaveAccount,
  };
}
