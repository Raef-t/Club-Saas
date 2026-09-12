"use client";

import { useMemo } from "react";
import PageHeader from "@/components/common/PageHeader";
import Button from "@/components/ui/Button";
import ConfirmDialog from "@/components/ui/ConfirmDialog";
import StatsGrid from "@/components/ui/StatsGrid";
import { PlusIcon } from "@/components/icons/Icons";
import { usePermissions } from "@/lib/PermissionContext";
import AppVersionModal from "./AppVersionModal";
import AppVersionsTable from "./AppVersionsTable";
import { useAppVersions } from "./useAppVersions";

export default function AppVersionsClient({ initialVersions = null }) {
  const { can, isSuperAdmin } = usePermissions();
  const canCreate = isSuperAdmin || can("app-version.create");
  const canUpdate = isSuperAdmin || can("app-version.update");
  const canDelete = isSuperAdmin || can("app-version.delete");

  const state = useAppVersions({ initialVersions });
  const hasActiveFilters =
    Boolean(state.search) || state.platformFilter !== "all" || state.statusFilter !== "all";

  const statItems = useMemo(
    () => [
      {
        title: "أحدث إصدار Android",
        value: state.stats.latestAndroid,
        helper: "آخر نسخة نشطة",
        tone: "green",
        iconKey: "android",
        compact: true,
      },
      {
        title: "أحدث إصدار iOS",
        value: state.stats.latestIos,
        helper: "آخر نسخة نشطة",
        tone: "blue",
        iconKey: "apple",
        compact: true,
      },
      {
        title: "إجمالي الإصدارات",
        value: state.stats.total.toLocaleString("ar"),
        helper: "كل النسخ المسجلة",
        tone: "yellow",
        iconKey: "schedule",
        compact: true,
      },
      {
        title: "الإصدارات النشطة",
        value: state.stats.activeCount.toLocaleString("ar"),
        helper: "متاحة للمتدربين",
        tone: "green",
        iconKey: "subscriptions",
        compact: true,
      },
    ],
    [state.stats],
  );

  function resetFilters() {
    state.setSearch("");
    state.setPlatformFilter("all");
    state.setStatusFilter("all");
  }

  return (
    <div className="space-y-6" dir="rtl">
      <PageHeader
        eyebrow="إدارة النظام"
        title="إصدارات تطبيق المتدرب"
        subtitle="إدارة إصدارات Android وiOS، وتحديد التحديثات الإجبارية والنسخ المتاحة للمتدربين."
        action={
          canCreate ? (
            <Button
              type="button"
              icon={<PlusIcon className="size-4" />}
              className="text-black"
              onClick={() => state.setCreateOpen(true)}
            >
              إضافة إصدار
            </Button>
          ) : null
        }
      />

      <StatsGrid items={statItems} />

      <AppVersionsTable
        versions={state.versions}
        totalVersions={state.allVersions.length}
        search={state.search}
        onSearchChange={state.setSearch}
        platformFilter={state.platformFilter}
        onPlatformFilterChange={state.setPlatformFilter}
        statusFilter={state.statusFilter}
        onStatusFilterChange={state.setStatusFilter}
        hasActiveFilters={hasActiveFilters}
        onResetFilters={resetFilters}
        onCreate={canCreate ? () => state.setCreateOpen(true) : undefined}
        onEdit={(item) => state.setEditTarget(item)}
        onDelete={(item) => state.setDeleteTarget(item)}
        onToggleStatus={state.handleToggleStatus}
        canUpdate={canUpdate}
        canDelete={canDelete}
        isLoading={state.isLoading}
        isToggling={state.isToggling}
      />

      <AppVersionModal
        open={state.createOpen}
        onClose={() => state.setCreateOpen(false)}
        onSubmit={state.handleCreate}
        isSubmitting={state.isCreating}
      />

      <AppVersionModal
        open={Boolean(state.editTarget)}
        initialData={state.editTarget}
        onClose={() => state.setEditTarget(null)}
        onSubmit={(data) => state.handleUpdate(state.editTarget.id, data)}
        isSubmitting={state.isUpdating}
      />

      <ConfirmDialog
        open={Boolean(state.deleteTarget)}
        onClose={() => state.setDeleteTarget(null)}
        onConfirm={state.handleDeleteConfirm}
        title="تأكيد حذف إصدار التطبيق"
        message={`هل أنت متأكد من حذف الإصدار ${state.deleteTarget?.version_number || ""}؟ سيتم حذف ملف التطبيق المرتبط به نهائياً.`}
        confirmLabel="حذف الإصدار"
        cancelLabel="إلغاء"
        tone="danger"
        isLoading={state.isDeleting}
      />
    </div>
  );
}
