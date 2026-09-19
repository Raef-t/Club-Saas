"use client";

import { useMemo, useState } from "react";
import CopyableUsername from "@/components/ui/CopyableUsername";
import PageHeader from "@/components/common/PageHeader";
import Button from "@/components/ui/Button";
import DataTable from "@/components/ui/DataTable";
import StatsGrid from "@/components/ui/StatsGrid";
import RowActions from "@/components/ui/RowActions";
import ToggleSwitch from "@/components/ui/ToggleSwitch";
import { SearchIcon } from "@/components/icons/Icons";
import { useUsers } from "./useUsers";
import UserRoleTabs from "./UserRoleTabs";
import UserPermissionsDrawer from "./UserPermissionsDrawer";
import { useResetPasswordMutation } from "@/lib/api/authApi";
import { useToggleUserStatusMutation } from "@/lib/api/usersApi";
import { useToast } from "@/components/ui/Toast";
import ConfirmDialog from "@/components/ui/ConfirmDialog";
import {
  canToggleUserStatus,
  getPasswordStatus,
  getUserRoleLabel,
  getUserRoles,
} from "./usersUtils";
import { PAGE_SIZE_OPTIONS } from "@/lib/pagination";
import { usePermissions } from "@/lib/PermissionContext";
import { DEFAULT_PASSWORD, hashDefaultPassword } from "@/lib/passwordHash";

export default function UsersClient({ initialUsers }) {
  const { user: currentUser, can, isSuperAdmin } = usePermissions();
  const canViewUserRoles = can("user-role.view") || can("user.view-any");
  const canAssignRoles = can("user-role.assign") || can("user-role.sync");
  const canToggleStatus = isSuperAdmin || can("user.toggle-status") || can("user.update");
  const [selectedUser, setSelectedUser] = useState(null);
  const [userToResetPassword, setUserToResetPassword] = useState(null);
  const [userToToggleStatus, setUserToToggleStatus] = useState(null);
  const toast = useToast();
  const [resetPassword, { isLoading: isResettingPassword }] = useResetPasswordMutation();
  const [toggleUserStatus, { isLoading: isTogglingStatus }] = useToggleUserStatusMutation();
  const {
    search,
    setSearch,
    roleFilter,
    setRoleFilter,
    users,
    stats,
    roleOptions,
    totalResults,
    pagination,
    isLoading,
    isRefreshing,
    errorMessage,
    retry,
  } = useUsers({ initialUsers });

  const handleResetPassword = async () => {
    if (!userToResetPassword) return;

    try {
      const passwordHash = await hashDefaultPassword();
      await resetPassword({
        user_id: userToResetPassword.id,
        password: passwordHash,
      }).unwrap();
      toast.success(
        `تم إعادة تعيين كلمة المرور للمستخدم ${userToResetPassword.name || userToResetPassword.username || ""} بنجاح إلى ${DEFAULT_PASSWORD}`,
      );
      setUserToResetPassword(null);
    } catch (error) {
      toast.error(error?.data?.message || "تعذر إعادة تعيين كلمة المرور. حاول مرة أخرى.");
    }
  };

  const handleToggleStatus = async () => {
    if (!userToToggleStatus) return;

    const { allowed, reason } = canToggleUserStatus(
      userToToggleStatus,
      currentUser,
      canToggleStatus,
    );
    if (!allowed) {
      toast.error(reason || "غير مسموح بتعديل حالة هذا الحساب.");
      setUserToToggleStatus(null);
      return;
    }

    try {
      const response = await toggleUserStatus(userToToggleStatus.id).unwrap();
      const nextIsActive = !userToToggleStatus.is_active;
      const defaultMsg = nextIsActive
        ? `تم تفعيل حساب المستخدم ${userToToggleStatus.name || userToToggleStatus.username || ""} بنجاح.`
        : `تم إيقاف حساب المستخدم ${userToToggleStatus.name || userToToggleStatus.username || ""} وإلغاء جلساته الفعالة بنجاح.`;
      toast.success(response?.message || defaultMsg);
      setUserToToggleStatus(null);
    } catch (error) {
      toast.error(error?.data?.message || "تعذر تغيير حالة الحساب. حاول مرة أخرى.");
    }
  };


  const columns = useMemo(
    () => [
      {
        key: "rowNumber",
        label: "#",
        type: "rowNumber",
        align: "center",
        sortable: false,
      },
      {
        key: "name",
        label: "الحساب",
        align: "start",
        render: (_, user) => (
          <div className="flex min-w-0 items-center justify-start gap-3 px-2">
            <div className="grid size-10 shrink-0 place-items-center rounded-full border border-app-yellow/25 bg-app-yellow-soft text-sm font-bold text-app-yellow">
              {Array.from(String(user.name || user.username || "م"))[0]}
            </div>
            <div className="min-w-0 text-start">
              <p className="truncate text-sm font-medium text-app-text">
                {user.name || "بدون اسم"}
              </p>
              <div className="mt-0.5">
                <CopyableUsername username={user.username || `#${user.id}`} />
              </div>
            </div>
          </div>
        ),
      },
      {
        key: "roles",
        label: "الدور",
        align: "center",
        sortValue: (user) => getUserRoles(user).map(getUserRoleLabel).join(" "),
        render: (_, user) => {
          const roles = getUserRoles(user);

          return roles.length > 0 ? (
            <div className="flex flex-wrap items-center justify-center gap-1.5">
              {roles.map((role) => (
                <span
                  key={role}
                  className="inline-flex rounded-full bg-[rgba(7,85,255,0.12)] px-2.5 py-1 text-[11px] font-medium text-app-blue"
                >
                  {getUserRoleLabel(role)}
                </span>
              ))}
            </div>
          ) : (
            <span className="text-app-muted-light">غير محدد</span>
          );
        },
      },
      {
        key: "custom_username",
        label: "اسم المستخدم المخصص",
        align: "center",
        render: (value) =>
          value ? (
            <CopyableUsername username={value} align="center" />
          ) : (
            <span className="text-app-muted-light">غير محدد</span>
          ),
      },
      {
        key: "is_active",
        label: "حالة الحساب",
        align: "center",
        sortValue: (user) => (user?.is_active ? 1 : 0),
        render: (_, user) => {
          const { allowed, reason } = canToggleUserStatus(user, currentUser, canToggleStatus);
          const isActive = Boolean(user?.is_active ?? true);
          const isPending = isTogglingStatus && userToToggleStatus?.id === user.id;

          return (
            <div
              className="flex items-center justify-center gap-2"
              onClick={(event) => event.stopPropagation()}
              title={reason || undefined}
            >
              <ToggleSwitch
                checked={isActive}
                onChange={() => setUserToToggleStatus(user)}
                disabled={!allowed || isPending}
                size="sm"
                ariaLabel={`تبديل حالة حساب ${user.name || user.username || ""}`}
              />
              <span
                className={`text-[11px] font-semibold ${
                  isActive ? "text-app-green" : "text-app-red"
                }`}
              >
                {isActive ? "نشط" : "موقوف"}
              </span>
            </div>
          );
        },
      },
      {
        key: "password_status",
        label: "حالة كلمة المرور",
        align: "center",
        sortValue: (user) => getPasswordStatus(user).label,
        render: (_, user) => {
          const status = getPasswordStatus(user);

          return (
            <div className="text-center">
              <span
                className={`inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold ${status.className}`}
              >
                {status.label}
              </span>
              <p className="mt-1.5 text-[10px] text-app-muted-light">{status.helper}</p>
            </div>
          );
        },
      },
      {
        key: "reset_password",
        label: "تغيير المرور",
        align: "center",
        sortable: false,
        render: (_, user) => (
          <Button
            type="button"
            tone="outline"
            className="h-8 px-3 text-[11px] whitespace-nowrap"
            onClick={() => setUserToResetPassword(user)}
          >
            إعادة تعيين
          </Button>
        ),
      },
      ...(canViewUserRoles
        ? [
            {
              key: "actions",
              label: "إجراءات",
              align: "center",
              sortable: false,
              render: (_, user) => (
                <RowActions onEdit={() => setSelectedUser(user)} editTitle="عرض الصلاحيات" />
              ),
            },
          ]
        : []),
    ],
    [
      canToggleStatus,
      canViewUserRoles,
      currentUser,
      isTogglingStatus,
      userToToggleStatus,
    ],
  );

  const tableColumnsGrid = useMemo(
    () =>
      [
        "60px",
        "minmax(200px,1.5fr)",
        "minmax(130px,1fr)",
        "minmax(130px,1fr)",
        "130px",
        "minmax(160px,1fr)",
        "120px",
        ...(canViewUserRoles ? ["80px"] : []),
      ].join(" "),
    [canViewUserRoles],
  );

  return (
    <div className="space-y-6" dir="rtl">
      <PageHeader
        eyebrow="لوحة التحكم"
        title="حسابات المستخدمين"
        subtitle="عرض حسابات النظام وأدوارها ومتابعة حالة تغيير كلمة المرور لكل مستخدم."
        action={
          <Button tone="outline" onClick={retry} disabled={isRefreshing}>
            {isRefreshing ? "جارٍ التحديث..." : "تحديث البيانات"}
          </Button>
        }
      />

      <StatsGrid items={stats} variant="compact" />

      <div className="space-y-3">
        <UserRoleTabs
          items={roleOptions}
          value={roleFilter}
          onChange={setRoleFilter}
          isRefreshing={isRefreshing}
        />

        <div
          id="user-accounts-panel"
          role="tabpanel"
          aria-labelledby={`user-role-tab-${roleFilter}`}
        >
          <DataTable
            title="قائمة حسابات المستخدمين"
            subtitle="اختر فئة من التبويبات، ثم ابحث بالاسم أو اسم المستخدم."
            columns={columns}
            rows={users}
            tableColumns={tableColumnsGrid}
            minWidth="1060px"
            defaultSortColumn="name"
            showAdd={false}
            showSearch={false}
            showFilter={false}
            showExport={false}
            isLoading={isLoading}
            currentPage={pagination?.currentPage || 1}
            totalPages={pagination?.lastPage || 1}
            totalItems={pagination?.total ?? totalResults}
            pageSize={pagination?.perPage || 10}
            pageSizeOptions={PAGE_SIZE_OPTIONS}
            onPageChange={pagination?.setPage}
            onPageSizeChange={pagination?.setPerPage}
            getRowKey={(user) => user.id}
            rowClassName="gap-2 px-3 py-3"
            headerClassName="gap-2 px-3"
            emptyMessage={
              errorMessage ? (
                <div className="space-y-3 text-center">
                  <p className="text-app-red">{errorMessage}</p>
                  <Button tone="outline" className="h-9 px-3 text-xs" onClick={retry}>
                    إعادة المحاولة
                  </Button>
                </div>
              ) : (
                "لا توجد حسابات مطابقة للبحث أو الدور المحدد."
              )
            }
            toolbarActions={
              <label className="relative block w-full sm:w-80">
                <SearchIcon className="pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2 text-app-muted-light" />
                <input
                  className="app-input h-10 w-full bg-app-card-soft ps-9 pe-3 text-right text-sm text-app-text outline-none transition focus:border-app-yellow/70"
                  value={search}
                  onChange={(event) => setSearch(event.target.value)}
                  placeholder="الاسم أو اسم المستخدم..."
                  type="search"
                  aria-label="البحث في حسابات المستخدمين"
                />
              </label>
            }
            toolbarMeta={
              <div className="text-sm text-app-muted-light">
                <span>النتائج: </span>
                <span className="font-medium text-app-text">
                  {totalResults.toLocaleString("ar")}
                </span>
                {isRefreshing && !isLoading && (
                  <span className="mt-1 block text-[11px] text-app-yellow">
                    جارٍ تحديث القائمة...
                  </span>
                )}
              </div>
            }
          />
        </div>
      </div>

      <UserPermissionsDrawer
        open={Boolean(selectedUser)}
        user={selectedUser}
        onClose={() => setSelectedUser(null)}
        canAssignRoles={canAssignRoles}
      />

      <ConfirmDialog
        open={Boolean(userToResetPassword)}
        onClose={() => setUserToResetPassword(null)}
        onConfirm={handleResetPassword}
        title="إعادة تعيين كلمة المرور"
        message={`هل أنت متأكد من رغبتك في إعادة تعيين كلمة المرور للمستخدم (${
          userToResetPassword?.name || userToResetPassword?.username || ""
        }) إلى "${DEFAULT_PASSWORD}"؟`}
        confirmLabel="إعادة تعيين"
        cancelLabel="إلغاء"
        tone="danger"
        isLoading={isResettingPassword}
      />

      <ConfirmDialog
        open={Boolean(userToToggleStatus)}
        onClose={() => setUserToToggleStatus(null)}
        onConfirm={handleToggleStatus}
        title={
          userToToggleStatus?.is_active
            ? "إيقاف حساب المستخدم"
            : "تفعيل حساب المستخدم"
        }
        message={
          userToToggleStatus?.is_active
            ? `هل أنت متأكد من رغبتك في إيقاف حساب (${
                userToToggleStatus?.name || userToToggleStatus?.username || ""
              })؟ سيتم فوراً إبطال جميع جلسات الدخول الفعالة للمستخدم وطرده من التطبيق والموقع.`
            : `هل أنت متأكد من رغبتك في تفعيل حساب (${
                userToToggleStatus?.name || userToToggleStatus?.username || ""
              })؟ سيتمكن المستخدم من تسجيل الدخول مجدداً بصورة طبيعية.`
        }
        confirmLabel={userToToggleStatus?.is_active ? "إيقاف الحساب" : "تفعيل الحساب"}
        cancelLabel="إلغاء"
        tone={userToToggleStatus?.is_active ? "danger" : "warning"}
        isLoading={isTogglingStatus}
      />
    </div>
  );
}

