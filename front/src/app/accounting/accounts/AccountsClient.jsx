"use client";

import { useState } from "react";
import Button from "@/components/ui/Button";
import SearchInput from "@/components/ui/SearchInput";
import LoadingSpinner from "@/components/ui/LoadingSpinner";
import {
  ChevronDownIcon,
  ChevronLeft,
  PencilIcon,
  FolderPlusIcon,
  PlusIcon,
  TipJarIcon,
  TrendUpIcon,
} from "@/components/icons/Icons";
import { useAccounts, ACCOUNT_TYPES } from "./useAccounts";

import AccountFormModal from "./AccountFormModal";
import AccountLedgerModal from "./AccountLedgerModal";

function TreeNode({ node, level = 0, onAddChild, onEdit, onLedger }) {
  const [isOpen, setIsOpen] = useState(true);
  const hasChildren = node.children && node.children.length > 0;
  const typeInfo = ACCOUNT_TYPES[node.type] || { label: node.type, color: "text-app-text" };

  return (
    <div className="select-none text-right" dir="rtl">
      <div
        className={`group flex items-center justify-between gap-3 rounded-xl border border-transparent p-2.5 transition-colors hover:border-app-line/40 hover:bg-app-card-soft/60 ${
          level === 0 ? "bg-app-card-soft/40 font-semibold mb-1" : "my-0.5"
        }`}
        style={{ paddingRight: `${Math.max(12, level * 24)}px` }}
      >
        <div className="flex items-center gap-2.5 min-w-0">
          {hasChildren ? (
            <button
              onClick={() => setIsOpen(!isOpen)}
              className="grid size-6 place-items-center rounded-lg bg-app-panel-soft text-app-muted hover:text-app-text"
              type="button"
            >
              {isOpen ? <ChevronDownIcon className="size-4" /> : <ChevronLeft className="size-4" />}
            </button>

          ) : (
            <span className="size-6 inline-block" />
          )}

          <span className="rounded-md bg-app-panel px-2 py-0.5 font-mono text-xs font-bold text-app-yellow border border-app-line/30">
            {node.code}
          </span>

          <span className="truncate text-sm font-medium text-app-text">{node.name}</span>

          {node.name_en && (
            <span className="hidden sm:inline text-xs text-app-muted font-sans" dir="ltr">
              ({node.name_en})
            </span>
          )}

          <span className={`rounded-full px-2 py-0.5 text-[10px] border font-medium ${typeInfo.color}`}>
            {typeInfo.label}
          </span>

          <span className="rounded-md bg-app-panel-soft px-1.5 py-0.5 text-[10px] text-app-muted">
            {node.currency}
          </span>

          {node.allow_manual_entry && (
            <span className="hidden md:inline rounded bg-emerald-500/10 px-1.5 py-0.5 text-[10px] text-emerald-400 font-medium border border-emerald-500/20">
              قيد يدوي
            </span>
          )}
        </div>

        {/* Action Buttons */}
        <div className="flex items-center gap-1 opacity-80 group-hover:opacity-100 transition-opacity">
          <button
            onClick={() => onLedger(node)}
            className="flex items-center gap-1 rounded-lg border border-app-line/40 bg-app-panel px-2.5 py-1 text-xs text-app-muted-light hover:border-app-yellow/50 hover:text-app-yellow transition"
            title="دفتر الأستاذ"
            type="button"
          >
            <TipJarIcon className="size-3.5" />
            <span className="hidden sm:inline">دفتر الأستاذ</span>
          </button>

          <button
            onClick={() => onAddChild(node)}
            className="grid size-7 place-items-center rounded-lg border border-app-line/40 bg-app-panel text-app-muted-light hover:border-app-yellow/50 hover:text-app-yellow transition"
            title="إضافة حساب فرعي"
            type="button"
          >
            <PlusIcon className="size-3.5" />
          </button>

          <button
            onClick={() => onEdit(node)}
            className="grid size-7 place-items-center rounded-lg border border-app-line/40 bg-app-card-soft text-app-muted-light hover:border-app-yellow hover:text-app-yellow transition"
            title="تعديل الحساب"
          >
            <PencilIcon className="size-3.5" />
          </button>
        </div>
      </div>

      {/* Render Children */}
      {hasChildren && isOpen && (
        <div className="border-r border-app-line/20 mr-3">
          {node.children.map((child) => (
            <TreeNode
              key={child.id}
              node={child}
              level={level + 1}
              onAddChild={onAddChild}
              onEdit={onEdit}
              onLedger={onLedger}
            />
          ))}
        </div>
      )}
    </div>
  );
}

export default function AccountsClient({ initialAccounts = [] }) {
  const {
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
    isLoading,
    isFormOpen,
    editingAccount,
    formErrors,
    isSaving,
    ledgerAccount,
    openCreateModal,
    openEditModal,
    closeFormModal,
    openLedgerModal,
    closeLedgerModal,
    handleSaveAccount,
  } = useAccounts({ initialAccounts });

  return (
    <div className="space-y-6" dir="rtl">
      {/* Toolbar: Search, Filters, View Modes, Add Button */}
      <div className="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-app-line/40 bg-app-card-soft/40 p-4">
        <div className="flex flex-1 flex-wrap items-center gap-3 min-w-[280px]">
          <div className="w-full sm:w-72">
            <SearchInput
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              placeholder="البحث باسم الحساب أو الكود..."
            />
          </div>

          <div className="flex flex-wrap items-center gap-1.5">
            <button
              onClick={() => setTypeFilter("all")}
              className={`rounded-xl px-3 py-1.5 text-xs font-medium transition ${
                typeFilter === "all"
                  ? "bg-app-yellow text-app-bg font-bold shadow"
                  : "bg-app-card-soft text-app-muted-light hover:bg-app-line-soft hover:text-app-text"
              }`}
            >
              الكل
            </button>
            {Object.entries(ACCOUNT_TYPES).map(([key, item]) => (
              <button
                key={key}
                onClick={() => setTypeFilter(key)}
                className={`rounded-xl px-3 py-1.5 text-xs font-medium transition ${
                  typeFilter === key
                    ? "bg-app-yellow text-app-bg font-bold shadow"
                    : "bg-app-card-soft text-app-muted-light hover:bg-app-line-soft hover:text-app-text"
                }`}
              >
                {item.label}
              </button>
            ))}
          </div>
        </div>

        <div className="flex items-center gap-3">
          <div className="flex rounded-xl border border-app-line/40 bg-app-card-soft p-1">
            <button
              onClick={() => setViewMode("tree")}
              className={`rounded-lg px-3 py-1 text-xs font-medium transition ${
                viewMode === "tree" ? "bg-app-yellow text-app-bg font-bold shadow" : "text-app-muted hover:text-app-text"
              }`}
            >
              عرض الشجرة
            </button>
            <button
              onClick={() => setViewMode("list")}
              className={`rounded-lg px-3 py-1 text-xs font-medium transition ${
                viewMode === "list" ? "bg-app-yellow text-app-bg font-bold shadow" : "text-app-muted hover:text-app-text"
              }`}
            >
              عرض الجدول
            </button>
          </div>

          <Button variant="primary" onClick={() => openCreateModal()} className="flex items-center gap-2">
            <FolderPlusIcon className="size-4" />
            <span>إضافة حساب جديد</span>
          </Button>
        </div>
      </div>

      {/* Main View Area */}
      <div className="rounded-2xl border border-app-line/40 bg-app-panel p-5">
        <div className="mb-4 flex items-center justify-between border-b border-app-line/30 pb-3">
          <div>
            <h2 className="text-lg font-bold text-app-text">دليل الحسابات الموحد</h2>
            <p className="text-xs text-app-muted">
              الهيكل الهرمي لحسابات الأصول والخصوم وحقوق الملكية والإيرادات والمصاريف
            </p>
          </div>
          <span className="rounded-lg bg-app-card-soft px-3 py-1 text-xs font-mono text-app-muted-light">
            {filteredAccounts.length} حساب
          </span>
        </div>

        {isLoading ? (
          <div className="flex h-64 items-center justify-center">
            <LoadingSpinner />
          </div>
        ) : filteredAccounts.length === 0 ? (
          <div className="rounded-xl border border-dashed border-app-line/60 p-12 text-center text-app-muted">
            لا توجد حسابات مطابقة لمعايير البحث.
          </div>
        ) : viewMode === "tree" ? (
          <div className="space-y-1">
            {accountTree.map((rootNode) => (
              <TreeNode
                key={rootNode.id}
                node={rootNode}
                onAddChild={openCreateModal}
                onEdit={openEditModal}
                onLedger={openLedgerModal}
              />
            ))}
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-right text-sm">
              <thead className="border-b border-app-line/40 text-xs text-app-muted">
                <tr>
                  <th className="p-3">الكود</th>
                  <th className="p-3">اسم الحساب</th>
                  <th className="p-3">الاسم بالإنجليزية</th>
                  <th className="p-3">النوع</th>
                  <th className="p-3">العملة</th>
                  <th className="p-3">القيد اليدوي</th>
                  <th className="p-3">الحالة</th>
                  <th className="p-3 text-left">الإجراءات</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-app-line/20 text-app-text text-xs">
                {filteredAccounts.map((acc) => {
                  const typeInfo = ACCOUNT_TYPES[acc.type] || { label: acc.type, color: "text-app-text" };
                  return (
                    <tr key={acc.id} className="hover:bg-app-card-soft/40 transition">
                      <td className="p-3 font-mono font-bold text-app-yellow">{acc.code}</td>
                      <td className="p-3 font-medium text-app-text">{acc.name}</td>
                      <td className="p-3 text-app-muted font-sans" dir="ltr">{acc.name_en || "-"}</td>
                      <td className="p-3">
                        <span className={`rounded-full px-2 py-0.5 text-[10px] border font-medium ${typeInfo.color}`}>
                          {typeInfo.label}
                        </span>
                      </td>
                      <td className="p-3 font-mono">{acc.currency}</td>
                      <td className="p-3">
                        {acc.allow_manual_entry ? (
                          <span className="text-emerald-400 font-medium">مسموح</span>
                        ) : (
                          <span className="text-app-muted">غير مسموح</span>
                        )}
                      </td>
                      <td className="p-3">
                        {acc.is_active ? (
                          <span className="inline-flex items-center gap-1 text-emerald-400">
                            <span className="size-1.5 rounded-full bg-emerald-400" />
                            نشط
                          </span>
                        ) : (
                          <span className="inline-flex items-center gap-1 text-rose-400">
                            <span className="size-1.5 rounded-full bg-rose-400" />
                            معطل
                          </span>
                        )}
                      </td>
                      <td className="p-3 text-left">
                        <div className="flex items-center justify-end gap-1.5">
                          <button
                            onClick={() => openLedgerModal(acc)}
                            className="rounded-lg border border-app-line/40 bg-app-card-soft px-2.5 py-1 text-xs text-app-muted-light hover:border-app-yellow hover:text-app-yellow transition"
                          >
                            كشف حساب
                          </button>
                          <button
                            onClick={() => openEditModal(acc)}
                            className="rounded-lg border border-app-line/40 bg-app-card-soft p-1.5 text-app-muted-light hover:border-app-yellow hover:text-app-yellow transition"
                            title="تعديل الحساب"
                          >
                            <PencilIcon className="size-4" />
                          </button>
                        </div>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {/* Account Create/Edit Modal */}
      <AccountFormModal
        isOpen={isFormOpen}
        onClose={closeFormModal}
        account={editingAccount}
        accounts={accounts}
        onSave={handleSaveAccount}
        isLoading={isSaving}
        errors={formErrors}
      />

      {/* General Ledger Modal */}
      <AccountLedgerModal
        isOpen={Boolean(ledgerAccount)}
        onClose={closeLedgerModal}
        account={ledgerAccount}
      />
    </div>
  );
}
