"use client";

import { useMemo, useState } from "react";
import Drawer from "@/components/ui/Drawer";
import Button from "@/components/ui/Button";
import LoadingSpinner from "@/components/ui/LoadingSpinner";
import { ChevronDownIcon, CheckCircleIcon, SealCheckIcon } from "@/components/icons/Icons";
import { useGetUserRolesQuery, useAssignUserRoleMutation } from "@/lib/api/usersApi";
import { useGetRolesQuery, useGetPermissionsQuery } from "@/lib/api/rolesApi";
import { useToast } from "@/components/ui/Toast";
import { getApiErrorMessage } from "@/lib/apiError";
import { getUserRoleLabel } from "./usersUtils";
import { groupPermissions, getPermissionLabel, getPermissionCollection } from "../roles/roleUtils";

// ─── helpers ──────────────────────────────────────────────────────────────────

/** جمع الصلاحيات من قائمة flat أو grouped */
function extractPermissions(data) {
  if (!data) return [];
  const d = data.data ?? data;
  if (Array.isArray(d?.flat)) return d.flat;
  if (Array.isArray(d?.permissions)) return d.permissions;
  if (Array.isArray(d)) return d;
  return [];
}

/** استخراج أسماء الأدوار المعينة للمستخدم */
function extractRoles(data) {
  const d = data?.data ?? data;
  if (Array.isArray(d?.roles)) return d.roles;
  return [];
}

/** استخراج أسماء الصلاحيات المرتبطة بمستخدم */
function extractUserPermissionNames(data) {
  const d = data?.data ?? data;
  const perms = Array.isArray(d?.permissions) ? d.permissions : [];
  return perms.map((p) => (typeof p === "string" ? p : p?.name)).filter(Boolean);
}

// ─── Mini accordion group ─────────────────────────────────────────────────────

function PermissionGroup({ group, activeNames }) {
  const [open, setOpen] = useState(false);
  const activeCount = group.permissions.filter((p) => activeNames.has(p.name)).length;
  const total = group.permissions.length;

  return (
    <div className="overflow-hidden rounded-xl border border-app-line bg-app-card-soft/40">
      <button
        type="button"
        onClick={() => setOpen((v) => !v)}
        className="flex w-full items-center justify-between gap-4 px-4 py-3 text-right transition hover:bg-app-card-hover"
        aria-expanded={open}
      >
        <div className="min-w-0">
          <p className="truncate text-sm font-medium text-app-text">{group.label}</p>
          <bdi className="mt-0.5 block text-[10px] text-app-muted-light" dir="ltr">
            {group.module}
          </bdi>
        </div>
        <div className="flex shrink-0 items-center gap-2">
          <span
            className={`rounded-full px-2 py-0.5 text-[11px] font-medium ${
              activeCount === total
                ? "bg-app-green/15 text-app-green"
                : activeCount > 0
                  ? "bg-app-yellow-soft text-app-yellow"
                  : "bg-app-panel-soft text-app-muted-light"
            }`}
          >
            {activeCount}/{total}
          </span>
          <ChevronDownIcon
            className={`size-4 text-app-muted-light transition-transform ${open ? "rotate-180" : ""}`}
          />
        </div>
      </button>

      {open && (
        <div className="border-t border-app-line px-4 pb-3 pt-2">
          <div className="space-y-1.5">
            {group.permissions.map((perm) => {
              const active = activeNames.has(perm.name);
              return (
                <div
                  key={perm.name}
                  className={`flex items-center gap-2.5 rounded-lg px-3 py-2 ${
                    active ? "bg-app-green/8" : "bg-app-panel-soft/60"
                  }`}
                >
                  <span
                    className={`size-2 shrink-0 rounded-full ${active ? "bg-app-green" : "bg-app-line"}`}
                    aria-hidden="true"
                  />
                  <div className="min-w-0 flex-1 text-right">
                    <p className="text-xs font-medium text-app-text">{getPermissionLabel(perm)}</p>
                    <bdi className="block break-all text-[10px] text-app-muted-light" dir="ltr">
                      {perm.name}
                    </bdi>
                  </div>
                  {active && (
                    <CheckCircleIcon className="size-4 shrink-0 text-app-green" aria-label="مُفعَّل" />
                  )}
                </div>
              );
            })}
          </div>
        </div>
      )}
    </div>
  );
}

// ─── Role Selector ────────────────────────────────────────────────────────────

function RoleSelector({ userId, currentRoles, availableRoles, onSuccess, canAssign }) {
  const [assignRole, { isLoading }] = useAssignUserRoleMutation();
  const toast = useToast();

  async function handleAssign(roleName) {
    if (currentRoles.includes(roleName) || !canAssign) return;
    try {
      await assignRole({ userId, roles: [roleName] }).unwrap();
      toast.success(`تم تعيين دور "${getUserRoleLabel(roleName)}" بنجاح`);
      onSuccess?.();
    } catch (error) {
      toast.error(getApiErrorMessage(error, "تعذر تعيين الدور. حاول مرة أخرى."));
    }
  }

  const visibleRoles = availableRoles.filter((r) => r.is_visible !== false);

  return (
    <div className="space-y-2">
      <p className="text-xs font-medium text-app-muted-light">الدور الحالي</p>
      <div className="flex flex-wrap gap-2">
        {currentRoles.length > 0 ? (
          currentRoles.map((r) => (
            <span
              key={r}
              className="inline-flex items-center gap-1.5 rounded-full bg-app-blue/12 px-3 py-1.5 text-xs font-medium text-app-blue"
            >
              <SealCheckIcon className="size-3.5" />
              {getUserRoleLabel(r)}
            </span>
          ))
        ) : (
          <span className="text-xs text-app-muted-light">لا يوجد دور معيّن</span>
        )}
      </div>

      {canAssign && visibleRoles.length > 0 && (
        <>
          <p className="mt-3 text-xs font-medium text-app-muted-light">تغيير الدور</p>
          <div className="flex flex-wrap gap-2">
            {visibleRoles.map((role) => {
              const isCurrent = currentRoles.includes(role.name);
              return (
                <button
                  key={role.id}
                  type="button"
                  disabled={isLoading || isCurrent}
                  onClick={() => handleAssign(role.name)}
                  className={`rounded-lg border px-3 py-1.5 text-xs font-medium transition ${
                    isCurrent
                      ? "cursor-default border-app-yellow/40 bg-app-yellow-soft text-app-yellow"
                      : "border-app-line bg-app-card-soft text-app-muted-light hover:border-app-yellow/50 hover:text-app-text disabled:opacity-50"
                  }`}
                >
                  {isLoading && !isCurrent ? "..." : getUserRoleLabel(role.name)}
                </button>
              );
            })}
          </div>
        </>
      )}
    </div>
  );
}

// ─── Main Drawer ──────────────────────────────────────────────────────────────

export default function UserPermissionsDrawer({ open, user, onClose, canAssignRoles }) {
  const userId = user?.id;

  const { data: userRolesData, isLoading: isLoadingUserRoles } = useGetUserRolesQuery(userId, {
    skip: !userId || !open,
  });

  const { data: allPermissionsData, isLoading: isLoadingPermissions } = useGetPermissionsQuery(
    {},
    { skip: !open },
  );

  const { data: rolesData, isLoading: isLoadingRoles } = useGetRolesQuery(undefined, {
    skip: !open,
  });

  const isLoading = isLoadingUserRoles || isLoadingPermissions || isLoadingRoles;

  const currentRoles = useMemo(() => extractRoles(userRolesData), [userRolesData]);

  const activePermissionNames = useMemo(
    () => new Set(extractUserPermissionNames(userRolesData)),
    [userRolesData],
  );

  const allPermissions = useMemo(
    () => getPermissionCollection(allPermissionsData),
    [allPermissionsData],
  );

  const groups = useMemo(() => groupPermissions(allPermissions), [allPermissions]);

  const availableRoles = useMemo(() => {
    const d = rolesData?.data ?? rolesData;
    return Array.isArray(d?.roles) ? d.roles : [];
  }, [rolesData]);

  const permissionStats = useMemo(() => {
    const total = allPermissions.length;
    const active = activePermissionNames.size;
    return { total, active };
  }, [allPermissions, activePermissionNames]);

  return (
    <Drawer
      open={open}
      onClose={onClose}
      side="end"
      title={user?.name || "معلومات المستخدم"}
      subtitle={user?.username ? `@${user.username}` : ""}
      className="max-w-[520px]"
      contentClassName="space-y-6"
      footer={
        <Button tone="outline" onClick={onClose} className="w-full">
          إغلاق
        </Button>
      }
    >
      {isLoading ? (
        <div className="flex min-h-52 items-center justify-center gap-3 text-sm text-app-muted-light">
          <LoadingSpinner className="size-5 text-app-yellow" />
          جارٍ تحميل بيانات المستخدم...
        </div>
      ) : (
        <>
          {/* بطاقة هوية المستخدم */}
          <div className="rounded-xl border border-app-line bg-app-card-soft/55 px-4 py-4">
            <div className="flex items-center gap-3">
              <div className="grid size-12 shrink-0 place-items-center rounded-full border border-app-yellow/25 bg-app-yellow-soft text-base font-bold text-app-yellow">
                {Array.from(String(user?.name || user?.username || "م"))[0]}
              </div>
              <div className="min-w-0 flex-1 text-right">
                <p className="truncate font-medium text-app-text">{user?.name || "بدون اسم"}</p>
                <bdi className="block text-xs text-app-muted-light" dir="ltr">
                  {user?.username}
                </bdi>
              </div>
            </div>
          </div>

          {/* تعيين الدور */}
          <section className="space-y-3 rounded-xl border border-app-line bg-app-card-soft/30 px-4 py-4">
            <h3 className="text-sm font-semibold text-app-text">الأدوار</h3>
            <RoleSelector
              userId={userId}
              currentRoles={currentRoles}
              availableRoles={availableRoles}
              canAssign={canAssignRoles}
            />
          </section>

          {/* إحصاءات الصلاحيات */}
          <div className="grid grid-cols-2 gap-3">
            <div className="rounded-xl border border-app-line bg-app-card-soft/40 px-4 py-3 text-right">
              <p className="text-[11px] text-app-muted-light">صلاحيات مفعلة</p>
              <p className="mt-1 text-xl font-semibold text-app-green">
                {permissionStats.active.toLocaleString("ar")}
              </p>
            </div>
            <div className="rounded-xl border border-app-line bg-app-card-soft/40 px-4 py-3 text-right">
              <p className="text-[11px] text-app-muted-light">إجمالي الصلاحيات</p>
              <p className="mt-1 text-xl font-semibold text-app-text">
                {permissionStats.total.toLocaleString("ar")}
              </p>
            </div>
          </div>

          {/* قائمة الصلاحيات مجمعة حسب الموديول */}
          <section className="space-y-2">
            <h3 className="text-sm font-semibold text-app-text">
              الصلاحيات المُشتقّة من الدور
            </h3>
            <p className="text-xs text-app-muted-light">
              هذه الصلاحيات مُعيَّنة انطلاقاً من الدور الحالي للمستخدم. لتعديلها، غيّر
              صلاحيات الدور من صفحة الأدوار.
            </p>
            {groups.length > 0 ? (
              <div className="mt-3 space-y-2">
                {groups.map((group) => (
                  <PermissionGroup
                    key={group.module}
                    group={group}
                    activeNames={activePermissionNames}
                  />
                ))}
              </div>
            ) : (
              <div className="flex min-h-32 items-center justify-center rounded-xl border border-dashed border-app-line text-sm text-app-muted-light">
                لا توجد صلاحيات مرتبطة.
              </div>
            )}
          </section>
        </>
      )}
    </Drawer>
  );
}
