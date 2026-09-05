"use client";

import Link from "next/link";
import Button from "@/components/ui/Button";
import ConfirmDialog from "@/components/ui/ConfirmDialog";
import EmptyState from "@/components/ui/EmptyState";
import LoadingSpinner from "@/components/ui/LoadingSpinner";
import PageHeader from "@/components/common/PageHeader";
import SearchInput from "@/components/ui/SearchInput";
import StatsGrid from "@/components/ui/StatsGrid";
import {
  PencilIcon,
  PlusIcon,
  SealCheckIcon,
  SettingsIcon,
  TrashIcon,
} from "@/components/icons/Icons";
import CreateRoleModal from "./CreateRoleModal";
import EditRoleModal from "./EditRoleModal";
import { getRolePresentation } from "./roleUtils";
import { useRoles } from "./useRoles";
import { usePermissions } from "@/lib/PermissionContext";

const TONE_CLASSES = {
  red: "border-app-red/30 bg-app-red/10 text-app-red",
  blue: "border-app-blue/30 bg-app-blue/10 text-app-blue",
  green: "border-app-green/30 bg-app-green/10 text-app-green",
  orange: "border-app-orange/30 bg-app-orange/10 text-app-orange",
  purple: "border-app-purple/30 bg-app-purple/10 text-app-purple",
  cyan: "border-cyan-400/30 bg-cyan-400/10 text-cyan-400",
  yellow: "border-app-yellow/30 bg-app-yellow-soft text-app-yellow",
};

function RoleCard({ role, onEdit, onDelete, canView, canDelete, canUpdate }) {
  const presentation = getRolePresentation(role);
  const permissionCount = Number(role.permissions_count ?? role.permissions?.length ?? 0);

  return (
    <article className="card-shell group flex min-h-64 flex-col rounded-2xl p-5 transition hover:-translate-y-0.5 hover:border-app-yellow/35">
      <div className="flex items-start justify-between gap-4">
        <div
          className={`grid size-12 shrink-0 place-items-center rounded-xl border ${TONE_CLASSES[presentation.tone] || TONE_CLASSES.yellow}`}
        >
          <SettingsIcon className="size-6" />
        </div>
        {role.is_protected ? (
          <span className="inline-flex items-center gap-1.5 rounded-full border border-app-blue/25 bg-app-blue/10 px-2.5 py-1 text-[11px] text-app-blue">
            <SealCheckIcon className="size-3.5" />
            دور محمي
          </span>
        ) : (
          <span className="rounded-full border border-app-line bg-app-card-soft px-2.5 py-1 text-[11px] text-app-muted-light">
            دور قابل للإدارة
          </span>
        )}
      </div>

      <div className="mt-4 flex-1 text-right">
        <h2 className="text-base font-medium text-app-text">{presentation.label}</h2>
        <bdi className="mt-1 block text-xs text-app-yellow" dir="ltr">
          {role.name}
        </bdi>
        <p className="mt-3 text-xs leading-6 text-app-muted-light">{presentation.description}</p>
      </div>

      <div className="mt-4 flex items-center justify-between border-t border-app-line pt-4">
        <div className="text-right">
          <span className="block text-[10px] text-app-muted-light">الصلاحيات المرتبطة</span>
          <span className="mt-0.5 block text-lg font-medium text-app-text">
            {permissionCount.toLocaleString("ar")}
          </span>
        </div>
        <div className="flex gap-2">
          {canUpdate && !role.is_protected && (
            <button
              type="button"
              onClick={() => onEdit(role)}
              className="grid size-9 place-items-center rounded-lg border border-app-line bg-app-card-soft text-app-muted-light transition hover:border-app-blue/60 hover:text-app-blue"
              title="تعديل اسم الدور"
              aria-label={`تعديل ${presentation.label}`}
            >
              <PencilIcon className="size-4" />
            </button>
          )}
          {canView && (
            <Link
              href={`/management/roles/${role.id}`}
              className="grid size-9 place-items-center rounded-lg border border-app-line bg-app-card-soft text-app-muted-light transition hover:border-app-yellow/60 hover:text-app-yellow"
              title="عرض وتعديل الصلاحيات"
              aria-label={`عرض وتعديل صلاحيات ${presentation.label}`}
            >
              <SettingsIcon className="size-4" />
            </Link>
          )}
          {canDelete && (
            <button
              type="button"
              onClick={() => onDelete(role)}
              disabled={role.is_protected}
              className="grid size-9 place-items-center rounded-lg border border-app-line bg-app-card-soft text-app-red transition hover:border-app-red/60 hover:bg-app-red/10 disabled:cursor-not-allowed disabled:opacity-35"
              title={role.is_protected ? "لا يمكن حذف دور محمي" : "حذف الدور"}
              aria-label={`حذف ${presentation.label}`}
            >
              <TrashIcon className="size-4" />
            </button>
          )}
        </div>
      </div>
    </article>
  );
}

export default function RolesClient({ initialRoles, initialPermissions }) {
  const state = useRoles({ initialRoles, initialPermissions });
  const { can } = usePermissions();
  const canCreate = can("role.create");
  const canView = can("role.view");
  const canUpdate = can("role.update");
  const canDelete = can("role.delete");

  return (
    <div className="space-y-6" dir="rtl">
      <PageHeader
        eyebrow="لوحة التحكم"
        title="الأدوار والصلاحيات"
        subtitle="أنشئ أدوار النظام، ووزّع الصلاحيات عليها حسب مسؤوليات كل مستخدم."
        action={
          canCreate ? (
            <Button
              type="button"
              icon={<PlusIcon className="size-4 text-black" />}
              className="font-semibold !text-black"
              onClick={() => state.setCreateOpen(true)}
            >
              إنشاء دور جديد
            </Button>
          ) : null
        }
      />

      <StatsGrid items={state.stats} variant="compact" />

      <section className="card-shell overflow-hidden rounded-2xl">
        <div className="flex flex-col gap-4 border-b border-app-line px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
          <div className="text-right">
            <h2 className="text-base font-medium text-app-text">قائمة الأدوار</h2>
            <p className="mt-1 text-xs text-app-muted-light">
              اضغط على زر التعديل لعرض تفاصيل الدور ومزامنة صلاحياته.
            </p>
          </div>
          <div className="flex flex-col gap-2 sm:flex-row">
            <SearchInput
              value={state.search}
              onChange={(event) => state.setSearch(event.target.value)}
              placeholder="ابحث باسم الدور..."
            />
            <Button
              type="button"
              tone="outline"
              onClick={state.retry}
              disabled={state.isRefreshing}
            >
              {state.isRefreshing ? "جارٍ التحديث..." : "تحديث"}
            </Button>
          </div>
        </div>

        <div className="p-5">
          {state.isLoading ? (
            <div className="flex min-h-72 items-center justify-center gap-3 text-sm text-app-muted-light">
              <LoadingSpinner className="size-6 text-app-yellow" />
              جارٍ تحميل الأدوار...
            </div>
          ) : state.errorMessage ? (
            <div className="flex min-h-72 flex-col items-center justify-center gap-4 rounded-xl border border-app-red/25 bg-app-red/10 p-6 text-center">
              <p className="text-sm text-app-red">{state.errorMessage}</p>
              <Button type="button" tone="outline" onClick={state.retry}>
                إعادة المحاولة
              </Button>
            </div>
          ) : state.roles.length ? (
            <div className="grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
              {state.roles.map((role) => (
                <RoleCard
                  key={role.id}
                  role={role}
                  onEdit={state.setEditTarget}
                  onDelete={state.setDeleteTarget}
                  canView={canView}
                  canUpdate={canUpdate}
                  canDelete={canDelete}
                />
              ))}
            </div>
          ) : (
            <EmptyState
              title={state.totalRoles ? "لا توجد أدوار مطابقة" : "لا توجد أدوار بعد"}
              description={
                state.totalRoles
                  ? "جرّب تغيير عبارة البحث."
                  : "ابدأ بإنشاء دور جديد ثم حدّد صلاحياته."
              }
            />
          )}
        </div>
      </section>

      <CreateRoleModal
        open={canCreate && state.createOpen}
        onClose={() => state.setCreateOpen(false)}
        onSubmit={state.handleCreate}
        isLoading={state.isCreating}
      />

      <EditRoleModal
        open={Boolean(state.editTarget)}
        role={state.editTarget}
        onClose={() => state.setEditTarget(null)}
        onSubmit={state.handleUpdate}
        isLoading={state.isUpdating}
      />

      <ConfirmDialog
        open={canDelete && Boolean(state.deleteTarget)}
        onClose={() => state.setDeleteTarget(null)}
        onConfirm={state.handleDelete}
        title="تأكيد حذف الدور"
        message={`هل أنت متأكد من حذف دور "${getRolePresentation(state.deleteTarget).label}"؟ قد يفقد المستخدمون المرتبطون به صلاحيات الوصول.`}
        confirmLabel="حذف الدور"
        isLoading={state.isDeleting}
      />
    </div>
  );
}
