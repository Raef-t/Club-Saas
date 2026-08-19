"use client";

import StatsGrid from "@/components/ui/StatsGrid";
import Button from "@/components/ui/Button";
import SearchInput from "@/components/ui/SearchInput";
import LoadingSpinner from "@/components/ui/LoadingSpinner";
import {
  FolderPlusIcon,
  PencilIcon,
  PlusIcon,
  TrendUpIcon,
} from "@/components/icons/Icons";

import { useCounterparties, COUNTERPARTY_TYPES } from "./useCounterparties";
import CounterpartyFormModal from "./CounterpartyFormModal";

export default function CounterpartiesClient({ initialCounterparties = [] }) {
  const {
    counterparties,
    filteredCounterparties,
    stats,
    search,
    setSearch,
    typeFilter,
    setTypeFilter,
    isLoading,
    isFormOpen,
    editingCounterparty,
    formErrors,
    isSaving,
    openCreateModal,
    openEditModal,
    closeFormModal,
    handleSaveCounterparty,
  } = useCounterparties({ initialCounterparties });

  const statsItems = [
    { label: "إجمالي الأطراف", value: stats.total },
    { label: "الموردين (Suppliers)", value: stats.suppliers },
    { label: "العملاء (Customers)", value: stats.customers },
    { label: "جهات أخرى", value: stats.others },
  ];

  return (
    <div className="space-y-6" dir="rtl">
      {/* Stats */}
      <StatsGrid items={statsItems} />

      {/* Toolbar */}
      <div className="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-app-line/40 bg-app-card-soft/40 p-4">
        <div className="flex flex-1 flex-wrap items-center gap-3 min-w-[280px]">
          <div className="w-full sm:w-72">
            <SearchInput
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              placeholder="البحث بالاسم أو الهاتف..."
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
            {Object.entries(COUNTERPARTY_TYPES).map(([key, item]) => (
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

        <Button variant="primary" onClick={openCreateModal} className="flex items-center gap-2">
          <PlusIcon className="size-4" />
          <span>إضافة طرف جديد</span>
        </Button>
      </div>

      {/* Table */}
      <div className="rounded-2xl border border-app-line/40 bg-app-panel p-5">
        <div className="mb-4 flex items-center justify-between border-b border-app-line/30 pb-3">
          <div>
            <h2 className="text-lg font-bold text-app-text">دليل الذمم والأطراف الخارجية</h2>
            <p className="text-xs text-app-muted">
              إدارة الموردين والعملاء والجهات الخارجية المرتبطة بالقيود وسندات الصرف والقبض
            </p>
          </div>
          <span className="rounded-lg bg-app-card-soft px-3 py-1 text-xs font-mono text-app-muted-light">
            {filteredCounterparties.length} جهة
          </span>
        </div>

        {isLoading ? (
          <div className="flex h-64 items-center justify-center">
            <LoadingSpinner />
          </div>
        ) : filteredCounterparties.length === 0 ? (
          <div className="rounded-xl border border-dashed border-app-line/60 p-12 text-center text-app-muted">
            لا توجد أطراف مسجلة مطابقة للبحث.
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-right text-sm">
              <thead className="border-b border-app-line/40 text-xs text-app-muted">
                <tr>
                  <th className="p-3">اسم الجهة</th>
                  <th className="p-3">التصنيف</th>
                  <th className="p-3">الهاتف</th>
                  <th className="p-3">البريد الإلكتروني</th>
                  <th className="p-3">الحالة</th>
                  <th className="p-3">ملاحظات</th>
                  <th className="p-3 text-left">الإجراءات</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-app-line/20 text-app-text text-xs">
                {filteredCounterparties.map((counterparty) => {
                  const typeInfo = COUNTERPARTY_TYPES[counterparty.type] || {
                    label: counterparty.type,
                    color: "text-app-text",
                  };

                  return (
                    <tr key={counterparty.id} className="hover:bg-app-card-soft/40 transition">
                      <td className="p-3 font-medium text-app-text">{counterparty.name}</td>
                      <td className="p-3">
                        <span
                          className={`rounded-full px-2 py-0.5 text-[10px] border font-medium ${typeInfo.color}`}
                        >
                          {typeInfo.label}
                        </span>
                      </td>
                      <td className="p-3 font-mono text-app-muted-light">{counterparty.phone || "-"}</td>
                      <td className="p-3 font-mono text-app-muted">{counterparty.email || "-"}</td>
                      <td className="p-3">
                        {counterparty.is_active ? (
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
                      <td className="p-3 text-app-muted max-w-xs truncate">{counterparty.notes || "-"}</td>
                      <td className="p-3 text-left">
                        <button
                          onClick={() => openEditModal(counterparty)}
                          className="rounded-lg border border-app-line/40 bg-app-card-soft p-1.5 text-app-muted-light hover:border-app-yellow hover:text-app-yellow transition"
                          title="تعديل"
                        >
                          <PencilIcon className="size-4" />
                        </button>

                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {/* Form Modal */}
      <CounterpartyFormModal
        isOpen={isFormOpen}
        onClose={closeFormModal}
        counterparty={editingCounterparty}
        onSave={handleSaveCounterparty}
        isLoading={isSaving}
        errors={formErrors}
      />
    </div>
  );
}
