"use client";

import StatsGrid from "@/components/ui/StatsGrid";
import Button from "@/components/ui/Button";
import SearchInput from "@/components/ui/SearchInput";
import LoadingSpinner from "@/components/ui/LoadingSpinner";
import {
  FolderPlusIcon,
  PlusIcon,
} from "@/components/icons/Icons";
import { usePeriods, PERIOD_STATUSES } from "./usePeriods";
import PeriodFormModal from "./PeriodFormModal";

export default function PeriodsClient({ initialPeriods = [] }) {
  const {
    periods,
    filteredPeriods,
    stats,
    search,
    setSearch,
    isLoading,
    isFormOpen,
    formErrors,
    isSaving,
    isProcessing,
    openCreateModal,
    closeFormModal,
    handleSavePeriod,
    handleClosePeriod,
    handleLockPeriod,
    handleReopenPeriod,
  } = usePeriods({ initialPeriods });

  const statsItems = [
    { label: "إجمالي الفترات", value: stats.total },
    { label: "فترات مفتوحة (Open)", value: stats.open },
    { label: "فترات مغلقة (Closed)", value: stats.closed },
    { label: "فترات مقفلة (Locked)", value: stats.locked },
  ];

  return (
    <div className="space-y-6" dir="rtl">
      {/* Stats */}
      <StatsGrid items={statsItems} />

      {/* Toolbar */}
      <div className="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-app-line/40 bg-app-card-soft/40 p-4">
        <div className="w-full sm:w-80">
          <SearchInput
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="البحث باسم الفترة المالية..."
          />
        </div>

        <Button variant="primary" onClick={openCreateModal} className="flex items-center gap-2">
          <PlusIcon className="size-4" />
          <span>إنشاء فترة مالية جديدة</span>
        </Button>
      </div>

      {/* Table */}
      <div className="rounded-2xl border border-app-line/40 bg-app-panel p-5">
        <div className="mb-4 flex items-center justify-between border-b border-app-line/30 pb-3">
          <div>
            <h2 className="text-lg font-bold text-app-text">الفترات المالية والإقفال</h2>
            <p className="text-xs text-app-muted">
              تنظيم الدورات المالية السنوية والشهرية وضبط أذونات الترحيل والإقفال
            </p>
          </div>
          <span className="rounded-lg bg-app-card-soft px-3 py-1 text-xs font-mono text-app-muted-light">
            {filteredPeriods.length} فترة
          </span>
        </div>

        {isLoading ? (
          <div className="flex h-64 items-center justify-center">
            <LoadingSpinner />
          </div>
        ) : filteredPeriods.length === 0 ? (
          <div className="rounded-xl border border-dashed border-app-line/60 p-12 text-center text-app-muted">
            لا توجد فترات مالية معرفة.
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-right text-sm">
              <thead className="border-b border-app-line/40 text-xs text-app-muted">
                <tr>
                  <th className="p-3">اسم الفترة</th>
                  <th className="p-3">تاريخ البداية</th>
                  <th className="p-3">تاريخ النهاية</th>
                  <th className="p-3">الحالة المحاسبية</th>
                  <th className="p-3 text-left">الإجراءات والتحكم</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-app-line/20 text-app-text text-xs">
                {filteredPeriods.map((period) => {
                  const statusInfo = PERIOD_STATUSES[period.status] || {
                    label: period.status,
                    color: "text-app-text",
                  };

                  return (
                    <tr key={period.id} className="hover:bg-app-card-soft/40 transition">
                      <td className="p-3 font-medium text-app-text">{period.name}</td>
                      <td className="p-3 font-mono text-app-muted">{period.start_date}</td>
                      <td className="p-3 font-mono text-app-muted">{period.end_date}</td>
                      <td className="p-3">
                        <span
                          className={`rounded-full px-2.5 py-0.5 text-[10px] border font-semibold ${statusInfo.color}`}
                        >
                          {statusInfo.label}
                        </span>
                      </td>
                      <td className="p-3 text-left">
                        <div className="flex items-center justify-end gap-2">
                          {period.status === "open" && (
                            <button
                              onClick={() => handleClosePeriod(period.id)}
                              disabled={isProcessing}
                              className="rounded-lg border border-amber-500/30 bg-amber-500/10 px-2.5 py-1 text-xs text-amber-400 hover:bg-amber-500/20 transition disabled:opacity-50"
                            >
                              إغلاق الفترة
                            </button>
                          )}

                          {period.status === "closed" && (
                            <>
                              <button
                                onClick={() => handleReopenPeriod(period.id)}
                                disabled={isProcessing}
                                className="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-2.5 py-1 text-xs text-emerald-400 hover:bg-emerald-500/20 transition disabled:opacity-50"
                              >
                                إعادة فتح
                              </button>
                              <button
                                onClick={() => handleLockPeriod(period.id)}
                                disabled={isProcessing}
                                className="rounded-lg border border-rose-500/30 bg-rose-500/10 px-2.5 py-1 text-xs text-rose-400 hover:bg-rose-500/20 transition disabled:opacity-50"
                              >
                                قفل نهائي
                              </button>
                            </>
                          )}

                          {period.status === "locked" && (
                            <span className="text-[11px] text-app-muted italic">
                              مقفلة ومحمية ضد التعديل
                            </span>
                          )}
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

      {/* Period Create Modal */}
      <PeriodFormModal
        isOpen={isFormOpen}
        onClose={closeFormModal}
        onSave={handleSavePeriod}
        isLoading={isSaving}
        errors={formErrors}
      />
    </div>
  );
}
