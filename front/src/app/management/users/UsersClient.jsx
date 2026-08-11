"use client";

import { useMemo } from "react";
import CopyableUsername from "@/components/ui/CopyableUsername";
import PageHeader from "@/components/common/PageHeader";
import Button from "@/components/ui/Button";
import DataTable from "@/components/ui/DataTable";
import StatsGrid from "@/components/ui/StatsGrid";
import { SearchIcon } from "@/components/icons/Icons";
import { useUsers } from "./useUsers";
import UserRoleTabs from "./UserRoleTabs";
import { getPasswordStatus, getUserRoleLabel, getUserRoles } from "./usersUtils";

const USER_TABLE_GRID =
  "60px minmax(220px,1.6fr) minmax(150px,1fr) minmax(150px,1fr) minmax(170px,1fr)";

export default function UsersClient({ initialUsers }) {
  const {
    search,
    setSearch,
    roleFilter,
    setRoleFilter,
    users,
    stats,
    roleOptions,
    totalResults,
    isLoading,
    isRefreshing,
    errorMessage,
    retry,
  } = useUsers({ initialUsers });

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
    ],
    [],
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
            tableColumns={USER_TABLE_GRID}
            minWidth="930px"
            defaultSortColumn="name"
            showAdd={false}
            showSearch={false}
            showFilter={false}
            showExport={false}
            isLoading={isLoading}
            pageSize={10}
            pageSizeOptions={[10, 20, 50]}
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
    </div>
  );
}
