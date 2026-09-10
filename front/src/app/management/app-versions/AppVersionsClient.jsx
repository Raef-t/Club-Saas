"use client";

import { useMemo } from "react";
import PageHeader from "@/components/common/PageHeader";
import Button from "@/components/ui/Button";
import SearchInput from "@/components/ui/SearchInput";
import ConfirmDialog from "@/components/ui/ConfirmDialog";
import EmptyState from "@/components/ui/EmptyState";
import LoadingSpinner from "@/components/ui/LoadingSpinner";
import { PlusIcon } from "@/components/icons/Icons";
import { usePermissions } from "@/lib/PermissionContext";
import { useAppVersions } from "./useAppVersions";
import AppVersionsTable from "./AppVersionsTable";
import AppVersionModal from "./AppVersionModal";

export default function AppVersionsClient({ initialVersions = null }) {
  const { can, isSuperAdmin } = usePermissions();

  const canCreate = isSuperAdmin || can("app-version.create");
  const canUpdate = isSuperAdmin || can("app-version.update");
  const canDelete = isSuperAdmin || can("app-version.delete");

  const {
    versions,
    stats,
    isLoading,
    isCreating,
    isUpdating,
    isDeleting,
    search,
    setSearch,
    platformFilter,
    setPlatformFilter,
    statusFilter,
    setStatusFilter,
    createOpen,
    setCreateOpen,
    editTarget,
    setEditTarget,
    deleteTarget,
    setDeleteTarget,
    handleCreate,
    handleUpdate,
    handleToggleStatus,
    handleDeleteConfirm,
  } = useAppVersions({ initialVersions });

  return (
    <div className="flex flex-col gap-6" dir="rtl">
      {/* Page Header */}
      <PageHeader
        eyebrow="لوحة التحكم والتطبيقات"
        title="إصدارات تطبيق المتدرب"
        subtitle="إدارة ورفع نسخ تطبيق المتدرب (Android / iOS)، التحكم بالتحديثات الإجبارية وإرسال الإشعارات التلقائية."
        action={
          canCreate ? (
            <Button
              type="button"
              variant="primary"
              onClick={() => setCreateOpen(true)}
              className="flex items-center gap-2"
            >
              <PlusIcon className="size-4" />
              <span>إضافة إصدار جديد</span>
            </Button>
          ) : null
        }
      />

      {/* Stats Cards */}
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        {/* Android Latest */}
        <div className="card-shell flex flex-col justify-between rounded-2xl border border-app-line bg-app-panel p-5 transition hover:border-app-yellow/30">
          <div className="flex items-center justify-between">
            <span className="text-xs font-medium text-app-muted">أحدث إصدار Android</span>
            <div className="grid size-9 place-items-center rounded-xl border border-emerald-500/30 bg-emerald-500/10 text-emerald-500">
              <svg className="size-4 fill-current" viewBox="0 0 24 24">
                <path d="M17.523 15.3414c-.5511 0-.9993-.4486-.9993-.9997s.4482-.9993.9993-.9993c.551 0 .9993.4482.9993.9993.0001.5511-.4483.9997-.9993.9997m-11.046 0c-.5511 0-.9993-.4486-.9993-.9997s.4482-.9993.9993-.9993c.551 0 .9993.4482.9993.9993 0 .5511-.4483.9997-.9993.9997m11.4045-6.02l1.9973-3.4592a.416.416 0 00-.1521-.5676.416.416 0 00-.5676.1521l-2.0223 3.503C15.5837 7.915 13.8427 7.4 12 7.4s-3.5837.515-5.1368 1.5498L4.8409 5.4468a.4161.4161 0 00-.5677-.1521.4157.4157 0 00-.152.5676l1.9973 3.4592C2.6889 11.1867.3432 14.6589 0 18.741h24c-.3432-4.0821-2.6889-7.5543-6.1185-9.4196" />
              </svg>
            </div>
          </div>
          <div className="mt-3">
            <span className="text-xl font-bold text-app-text" dir="ltr">
              {stats.latestAndroid}
            </span>
          </div>
        </div>

        {/* iOS Latest */}
        <div className="card-shell flex flex-col justify-between rounded-2xl border border-app-line bg-app-panel p-5 transition hover:border-app-yellow/30">
          <div className="flex items-center justify-between">
            <span className="text-xs font-medium text-app-muted">أحدث إصدار iOS</span>
            <div className="grid size-9 place-items-center rounded-xl border border-sky-500/30 bg-sky-500/10 text-sky-500">
              <svg className="size-4 fill-current" viewBox="0 0 24 24">
                <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M15.97 6.37c.61-.75 1.04-1.8 0.92-2.85-.9.04-2 .6-2.64 1.35-.56.65-1.05 1.71-.92 2.74 1.01.08 2.03-.49 2.64-1.24z" />
              </svg>
            </div>
          </div>
          <div className="mt-3">
            <span className="text-xl font-bold text-app-text" dir="ltr">
              {stats.latestIos}
            </span>
          </div>
        </div>

        {/* Total Releases */}
        <div className="card-shell flex flex-col justify-between rounded-2xl border border-app-line bg-app-panel p-5 transition hover:border-app-yellow/30">
          <div className="flex items-center justify-between">
            <span className="text-xs font-medium text-app-muted">إجمالي الإصدارات</span>
            <div className="grid size-9 place-items-center rounded-xl border border-app-yellow/30 bg-app-yellow-soft text-app-yellow font-bold text-sm">
              ∑
            </div>
          </div>
          <div className="mt-3">
            <span className="text-xl font-bold text-app-text">
              {stats.total}
            </span>
          </div>
        </div>

        {/* Active Releases */}
        <div className="card-shell flex flex-col justify-between rounded-2xl border border-app-line bg-app-panel p-5 transition hover:border-app-yellow/30">
          <div className="flex items-center justify-between">
            <span className="text-xs font-medium text-app-muted">النسخ النشطة</span>
            <div className="grid size-9 place-items-center rounded-xl border border-app-green/30 bg-[rgba(19,172,73,0.18)] text-app-green font-bold text-sm">
              ✓
            </div>
          </div>
          <div className="mt-3">
            <span className="text-xl font-bold text-app-text">
              {stats.activeCount}
            </span>
          </div>
        </div>
      </div>

      {/* Filter and Search Bar */}
      <div className="flex flex-col sm:flex-row items-center justify-between gap-3">
        <div className="w-full sm:w-72">
          <SearchInput
            placeholder="بحث برقم الإصدار أو الملاحظات..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
          />
        </div>

        <div className="flex w-full sm:w-auto items-center gap-2">
          {/* Platform Filter */}
          <select
            value={platformFilter}
            onChange={(e) => setPlatformFilter(e.target.value)}
            className="rounded-xl border border-app-line bg-app-panel px-3 py-2 text-xs font-medium text-app-text outline-none focus:border-app-yellow transition"
          >
            <option value="all">كافة الأنظمة</option>
            <option value="android">Android</option>
            <option value="ios">iOS</option>
          </select>

          {/* Status Filter */}
          <select
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value)}
            className="rounded-xl border border-app-line bg-app-panel px-3 py-2 text-xs font-medium text-app-text outline-none focus:border-app-yellow transition"
          >
            <option value="all">كافة الحالات</option>
            <option value="active">مفعل فقط</option>
            <option value="inactive">معطل فقط</option>
          </select>
        </div>
      </div>

      {/* Main Table or Empty State */}
      {isLoading && versions.length === 0 ? (
        <div className="flex h-64 items-center justify-center">
          <LoadingSpinner className="size-8 text-app-yellow" />
        </div>
      ) : versions.length === 0 ? (
        <EmptyState
          title="لا توجد إصدارات مسجلة"
          description={
            search || platformFilter !== "all" || statusFilter !== "all"
              ? "لم يتم العثور على إصدارات تطابق معايير البحث والتصفية المحددة."
              : "لم يتم رفع أو تسجيل أي إصدار لتطبيق المتدرب حتى الآن. يمكنك إضافة الإصدار الأول الآن."
          }
          action={
            canCreate && !search && platformFilter === "all" && statusFilter === "all" ? (
              <Button
                type="button"
                variant="primary"
                onClick={() => setCreateOpen(true)}
                className="mt-2"
              >
                إضافة الإصدار الأول
              </Button>
            ) : null
          }
        />
      ) : (
        <AppVersionsTable
          versions={versions}
          onEdit={(item) => setEditTarget(item)}
          onDelete={(item) => setDeleteTarget(item)}
          onToggleStatus={handleToggleStatus}
          canUpdate={canUpdate}
          canDelete={canDelete}
        />
      )}

      {/* Create Modal */}
      {createOpen && (
        <AppVersionModal
          open={createOpen}
          onClose={() => setCreateOpen(false)}
          onSubmit={handleCreate}
          isSubmitting={isCreating}
        />
      )}

      {/* Edit Modal */}
      {Boolean(editTarget) && (
        <AppVersionModal
          open={Boolean(editTarget)}
          initialData={editTarget}
          onClose={() => setEditTarget(null)}
          onSubmit={(data) => handleUpdate(editTarget.id, data)}
          isSubmitting={isUpdating}
        />
      )}

      {/* Delete Confirmation Dialog */}
      {Boolean(deleteTarget) && (
        <ConfirmDialog
          open={Boolean(deleteTarget)}
          onClose={() => setDeleteTarget(null)}
          onConfirm={handleDeleteConfirm}
          title="تأكيد حذف إصدار التطبيق"
          description={`هل أنت متأكد من رغبتك في حذف الإصدار ${deleteTarget?.version_number}؟ سيتم حذف ملف التطبيق المرتبط به نهائياً من السيرفر.`}
          confirmLabel="نعم، احذف الإصدار"
          cancelLabel="إلغاء"
          variant="danger"
          loading={isDeleting}
        />
      )}
    </div>
  );
}
