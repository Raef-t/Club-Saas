"use client";

import { useMemo } from "react";
import PageHeader from "@/components/common/PageHeader";
import Button from "@/components/ui/Button";
import ConfirmDialog from "@/components/ui/ConfirmDialog";
import DataTable from "@/components/ui/DataTable";
import Drawer from "@/components/ui/Drawer";
import Dropdown from "@/components/ui/Dropdown";
import RowActions from "@/components/ui/RowActions";
import StatsGrid from "@/components/ui/StatsGrid";
import { FilterIcon, PlusIcon, SearchIcon } from "@/components/icons/Icons";
import { formatLocalizedName, formatMoney } from "@/lib/utils";
import { getWorkStatusMeta, WORK_STATUS_OPTIONS } from "@/lib/workStatus";
import {
  STAFF_EMPLOYMENT_LABELS,
  STAFF_FILTER_ROLE_OPTIONS,
  STAFF_GENDER_LABELS,
  STAFF_ROLE_LABELS,
  STAFF_TABLE_GRID,
} from "./staffConstants";
import StaffDetails from "./StaffDetails";
import { getStaffBranchNames, getStaffEditHref, resolveStaffPhotoUrl } from "./staffUtils";
import { useStaff } from "./useStaff";

export default function StaffClient({ initialData }) {
  const {
    search,
    setSearch,
    branchFilter,
    setBranchFilter,
    roleFilter,
    setRoleFilter,
    genderFilter,
    setGenderFilter,
    workStatusFilter,
    setWorkStatusFilter,
    drawerMode,
    setDrawerMode,
    setSelectedStaffId,
    isLoading,
    error,
    refetch,
    filteredStaff,
    stats,
    selectedStaff,
    detailsStaff,
    detailsError,
    isFetchingDetails,
    isDeleting,
    handleDelete,
    confirmDelete,
    closeDeleteConfirm,
    deleteConfirmOpen,
    itemToDelete,
    deleteConfirmation,
    setDeleteConfirmation,
    getEditInitialValues,
    branches,
    closeDrawer,
  } = useStaff({ initialData });

  const columns = useMemo(
    () => [
      {
        key: "name",
        label: "الموظف",
        align: "start",
        sortValue: (staff) => staff.person?.full_name || "",
        render: (_, staff) => {
          const photoUrl = resolveStaffPhotoUrl(staff.person?.photo_url || staff.person?.photo);
          return (
            <div className="flex items-center justify-start gap-3 px-3">
              {photoUrl ? (
                <div
                  className="size-9 shrink-0 rounded-full border border-app-line bg-cover bg-center"
                  style={{ backgroundImage: `url(${photoUrl})` }}
                />
              ) : (
                <div className="grid size-9 shrink-0 place-items-center rounded-full border border-app-line bg-app-card-soft text-xs font-bold text-app-yellow">
                  {staff.person?.full_name?.charAt(0) || "م"}
                </div>
              )}
              <div className="min-w-0 text-right">
                <p className="truncate text-sm font-medium text-app-text">
                  {staff.person?.full_name || "-"}
                </p>
                <p className="mt-0.5 truncate text-[10px] text-app-muted-light" dir="ltr">
                  {staff.username || staff.person?.phone_number || `#${staff.id}`}
                </p>
              </div>
            </div>
          );
        },
      },
      {
        key: "role",
        label: "الدور",
        align: "center",
        sortValue: (staff) => STAFF_ROLE_LABELS[staff.role] || staff.role || "",
        render: (value) => (
          <span className="rounded-full bg-app-blue/10 px-2.5 py-1 text-xs text-app-blue">
            {STAFF_ROLE_LABELS[value] || value || "-"}
          </span>
        ),
      },
      {
        key: "branches",
        label: "الفروع",
        align: "center",
        sortValue: (staff) => getStaffBranchNames(staff, branches).join("، "),
        render: (_, staff) => {
          const names = getStaffBranchNames(staff, branches);
          return <span className="text-xs text-app-muted-light">{names.join("، ") || "-"}</span>;
        },
      },
      {
        key: "employment_type",
        label: "نوع التوظيف",
        align: "center",
        sortValue: (staff) => STAFF_EMPLOYMENT_LABELS[staff.employment_type] || staff.employment_type || "",
        render: (value) => (
          <span className="text-xs text-app-muted-light">
            {STAFF_EMPLOYMENT_LABELS[value] || value || "-"}
          </span>
        ),
      },
      {
        key: "base_salary",
        label: "الراتب الأساسي",
        align: "center",
        sortValue: (staff) => Number(staff.base_salary || 0),
        render: (value) => (
          <span className="text-xs font-semibold text-app-green">{formatMoney(value)}</span>
        ),
      },
      {
        key: "work_status",
        label: "الحالة",
        align: "center",
        sortValue: (staff) => getWorkStatusMeta(staff).label,
        render: (_, staff) => {
          const status = getWorkStatusMeta(staff);
          return (
            <span
              className={`inline-flex rounded-full px-2.5 py-1 text-[10px] font-semibold ${status.className}`}
            >
              {status.label}
            </span>
          );
        },
      },
      {
        key: "actions",
        label: "الإجراءات",
        align: "center",
        sortable: false,
        render: (_, staff) => (
          <RowActions
            disabled={isDeleting}
            editHref={getStaffEditHref(staff)}
            onDelete={() => handleDelete(staff)}
          />
        ),
      },
    ],
    [branches, handleDelete, isDeleting],
  );

  const branchOptions = useMemo(
    () => [
      { value: "all", label: "كل الفروع" },
      ...branches.map((branch) => ({
        value: String(branch.id),
        label: formatLocalizedName(branch.name),
      })),
    ],
    [branches],
  );

  const roleOptions = useMemo(
    () => [{ value: "all", label: "كل الأدوار" }, ...STAFF_FILTER_ROLE_OPTIONS],
    [],
  );
  const genderOptions = useMemo(
    () => [
      { value: "all", label: "كل الأجناس" },
      ...Object.entries(STAFF_GENDER_LABELS).map(([value, label]) => ({ value, label })),
    ],
    [],
  );
  const statusOptions = useMemo(
    () => [{ value: "all", label: "كل الحالات" }, ...WORK_STATUS_OPTIONS],
    [],
  );

  return (
    <div className="space-y-6" dir="rtl">
      <PageHeader
        eyebrow="إدارة النظام"
        title="إدارة الموظفين"
        subtitle="إضافة الموظفين وإدارة بياناتهم الوظيفية والفروع والورديات وحالة العمل."
        action={
          <Button
            href="/management/staff/create"
            icon={<PlusIcon className="size-4 text-black" style={{ color: "#000000" }} />}
            className="text-black"
            style={{ color: "#000000" }}
          >
            إضافة موظف
          </Button>
        }
      />

      <StatsGrid items={stats} />

      <DataTable
        title="قائمة الموظفين"
        columns={columns}
        rows={filteredStaff}
        minWidth="1020px"
        tableColumns={STAFF_TABLE_GRID}
        showAdd={false}
        showSearch={false}
        showFilter={false}
        showExport={false}
        defaultSortColumn="name"
        isLoading={isLoading}
        emptyMessage={
          error ? (
            <div className="space-y-3 text-center">
              <p className="text-app-red">تعذر تحميل بيانات الموظفين.</p>
              <Button tone="outline" className="h-9 px-3 text-xs" onClick={refetch}>
                إعادة المحاولة
              </Button>
            </div>
          ) : (
            "لا يوجد موظفون مطابقون للبحث والفلاتر الحالية."
          )
        }
        rowClassName="gap-2 px-3 py-4"
        headerClassName="gap-2 px-3"
        totalPages={0}
        onRowClick={(staff) => {
          setSelectedStaffId(staff.id);
          setDrawerMode("details");
        }}
        getRowKey={(staff) => staff.id}
        toolbarActions={
          <div className="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
            <label className="relative block w-full sm:w-72">
              <SearchIcon className="pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2 text-app-muted-light" />
              <input
                className="app-input h-10 w-full bg-app-card-soft ps-9 pe-3 text-right text-sm text-app-text outline-none transition focus:border-app-yellow/70"
                value={search}
                onChange={(event) => setSearch(event.target.value)}
                placeholder="الاسم أو الهاتف أو اسم المستخدم..."
                type="search"
                aria-label="البحث في الموظفين"
              />
            </label>

            <Dropdown
              className="min-w-40 text-app-text"
              icon={FilterIcon}
              value={roleFilter}
              options={roleOptions}
              onChange={setRoleFilter}
            />
            <Dropdown
              className="min-w-40 text-app-text"
              icon={FilterIcon}
              value={genderFilter}
              options={genderOptions}
              onChange={setGenderFilter}
            />
            <Dropdown
              className="min-w-40 text-app-text"
              icon={FilterIcon}
              value={workStatusFilter}
              options={statusOptions}
              onChange={setWorkStatusFilter}
            />
          </div>
        }
        toolbarMeta={
          <p className="text-sm text-app-muted-light">
            النتائج:{" "}
            <span className="font-medium text-app-text">
              {filteredStaff.length.toLocaleString("ar")}
            </span>
          </p>
        }
      />

      <Drawer
        open={drawerMode === "details"}
        onClose={closeDrawer}
        title="تفاصيل الموظف"
        subtitle={detailsStaff?.person?.full_name || selectedStaff?.person?.full_name || ""}
      >
        <StaffDetails
          staff={detailsStaff || selectedStaff}
          branches={branches}
          isLoading={isFetchingDetails}
          error={detailsError}
        />
      </Drawer>

      <ConfirmDialog
        open={deleteConfirmOpen}
        onClose={closeDeleteConfirm}
        onConfirm={confirmDelete}
        title="تأكيد حذف الموظف"
        message={`هل أنت متأكد من حذف الموظف "${itemToDelete?.person?.full_name || ""}"؟ لا يمكن التراجع عن هذا الإجراء.`}
        requiredConfirmation="delete"
        confirmationValue={deleteConfirmation}
        onConfirmationChange={setDeleteConfirmation}
        confirmationLabel="اكتب كلمة delete لتأكيد الحذف"
        isLoading={isDeleting}
      />
    </div>
  );
}
