"use client";

import { useEffect, useMemo, useRef } from "react";
import Button from "@/components/ui/Button";
import LoadingSpinner from "@/components/ui/LoadingSpinner";
import PageHeader from "@/components/common/PageHeader";
import StatsGrid from "@/components/ui/StatsGrid";
import { SealCheckIcon } from "@/components/icons/Icons";
import { useToast } from "@/components/ui/Toast";
import { getApiErrorMessage } from "@/lib/apiError";
import {
  useGetPermissionsQuery,
  useGetRoleQuery,
  useGetRolesQuery,
  useSyncRolePermissionsMutation,
} from "@/lib/api/rolesApi";
import {
  getPermissionCollection,
  getPermissionsFromRoles,
  getRoleCollection,
  getRolePermissionNames,
  getRolePresentation,
  getRoleRecord,
  groupPermissions,
  mergePermissionCatalog,
  normalizePermission,
} from "../roleUtils";
import RolePermissionsAccordion from "./RolePermissionsAccordion";
import { usePermissions } from "@/lib/PermissionContext";

export default function RoleDetailsClient({
  roleId,
  initialRole,
  initialRoles,
  initialPermissions,
}) {
  const toast = useToast();
  const { can } = usePermissions();
  const canSyncPermissions = can("role.sync-permissions");
  const roleQuery = useGetRoleQuery(roleId);
  const rolesQuery = useGetRolesQuery();
  const permissionsQuery = useGetPermissionsQuery();
  const [syncPermissions, { isLoading: isSaving }] = useSyncRolePermissionsMutation();

  const role = getRoleRecord(roleQuery.currentData || initialRole);
  const roles = getRoleCollection(rolesQuery.currentData || initialRoles);

  const seenPermissionsRef = useRef(new Map());

  useEffect(() => {
    if (Array.isArray(role?.permissions)) {
      role.permissions.forEach((permission) => {
        const normalized = normalizePermission(permission);
        if (normalized) {
          seenPermissionsRef.current.set(normalized.name, normalized);
        }
      });
    }
  }, [role?.permissions]);

  const catalog = useMemo(() => {
    const endpointPermissions = getPermissionCollection(
      permissionsQuery.currentData || initialPermissions,
    );
    const seen = Array.from(seenPermissionsRef.current.values());
    return mergePermissionCatalog([...endpointPermissions, ...seen], {
      permissions: getPermissionsFromRoles([...roles, role].filter(Boolean)),
    });
  }, [initialPermissions, permissionsQuery.currentData, role, roles]);

  const rolePermissionNames = getRolePermissionNames(role);
  const presentation = getRolePresentation(role);

  async function handleSave(selectedNames) {
    try {
      await syncPermissions({
        id: roleId,
        permissions: Array.from(selectedNames).sort(),
      }).unwrap();
      toast.success("تم تحديث صلاحيات الدور بنجاح");
      return true;
    } catch (error) {
      toast.error(getApiErrorMessage(error, "تعذر تحديث الصلاحيات. حاول مرة أخرى."));
      return false;
    }
  }

  if (!role && roleQuery.isLoading) {
    return (
      <div className="flex min-h-[55vh] items-center justify-center gap-3 text-sm text-app-muted-light">
        <LoadingSpinner className="size-6 text-app-yellow" />
        جارٍ تحميل تفاصيل الدور...
      </div>
    );
  }

  if (!role || roleQuery.error) {
    return (
      <div className="flex min-h-[55vh] flex-col items-center justify-center gap-4 rounded-2xl border border-app-red/25 bg-app-red/10 p-6 text-center">
        <p className="text-sm text-app-red">تعذر تحميل تفاصيل الدور.</p>
        <Button type="button" tone="outline" onClick={roleQuery.refetch}>
          إعادة المحاولة
        </Button>
      </div>
    );
  }

  const stats = [
    {
      title: "إجمالي الصلاحيات",
      value: catalog.length.toLocaleString("ar"),
      helper: "كافة صلاحيات النظام المتاحة",
      tone: "yellow",
      compact: true,
    },
    {
      title: "الصلاحيات المفعلة",
      value: rolePermissionNames.length.toLocaleString("ar"),
      helper: "مفعلة لهذا الدور حالياً",
      tone: "green",
      compact: true,
    },
    {
      title: "أقسام الصلاحيات",
      value: groupPermissions(catalog).length.toLocaleString("ar"),
      helper: "كل قسم يظهر كقائمة مستقلة",
      tone: "blue",
      compact: true,
    },
  ];

  return (
    <div className="space-y-6" dir="rtl">
      <PageHeader
        eyebrow="الأدوار والصلاحيات"
        title={presentation.label}
        subtitle={presentation.description}
        action={
          <Button href="/management/roles" tone="outline">
            العودة إلى الأدوار
          </Button>
        }
      />

      <div className="flex flex-wrap items-center gap-3 rounded-xl border border-app-line bg-app-card-soft/55 px-4 py-3 text-xs">
        <bdi className="text-app-yellow" dir="ltr">
          {role.name}
        </bdi>
        <span className="text-app-muted-light">رقم الدور: #{role.id}</span>
        {role.is_protected && (
          <span className="inline-flex items-center gap-1.5 rounded-full bg-app-blue/10 px-2.5 py-1 text-app-blue">
            <SealCheckIcon className="size-3.5" />
            دور محمي
          </span>
        )}
      </div>

      <StatsGrid items={stats} variant="compact" />

      <RolePermissionsAccordion
        key={`${role.id}:${rolePermissionNames.slice().sort().join("|")}`}
        permissions={catalog}
        selectedPermissionNames={rolePermissionNames}
        onSave={handleSave}
        isSaving={isSaving}
        readOnly={!canSyncPermissions}
        title="صلاحيات الدور"
        description="افتح كل قسم وحدد العمليات التي ترغب في منحها أو سحبها من هذا الدور."
      />
    </div>
  );
}
