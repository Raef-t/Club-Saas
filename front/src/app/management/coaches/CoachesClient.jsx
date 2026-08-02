"use client";

import CoachDetails from "./CoachDetails";
import { CoachCreateForm } from "./CoachForm";

import { useMemo, useState, useEffect } from "react";
import PageHeader from "@/components/common/PageHeader";
import Button from "@/components/ui/Button";
import DataTable from "@/components/ui/DataTable";
import Drawer from "@/components/ui/Drawer";
import RowActions from "@/components/ui/RowActions";
import SkeletonPage from "@/components/ui/Skeleton";
import StatsGrid from "@/components/ui/StatsGrid";
import Dropdown from "@/components/ui/Dropdown";
import { PlusIcon, SearchIcon, FilterIcon, TrashIcon } from "@/components/icons/Icons";
import ConfirmDialog from "@/components/ui/ConfirmDialog";
import Checkbox from "@/components/ui/Checkbox";
import { coachFormSchema, coachEditSchema } from "@/lib/validations/coachesSchema";
import { useCoaches } from "./useCoaches";
import PhoneField from "@/components/forms/PhoneField";
import DatePickerSmart from "@/components/forms/DatePickerSmart";
import { useGetBranchShiftsQuery, useGetBranchSettingsQuery } from "@/lib/api/branchesApi";
import { UploadBox } from "@/components/forms/UploadBox";
import { Field } from "@/components/forms/Field";
import DetailItem from "@/components/ui/DetailItem";
import {
  COACH_GENDER_LABELS as genderLabels,
  COACH_TABLE_GRID as TABLE_GRID_COLUMNS,
  DAYS_OF_WEEK as daysOfWeek,
  EMPLOYMENT_LABELS as employmentLabels,
  EMPLOYMENT_TYPES as employmentTypes,
  SHIFT_GENDER_LABELS as shiftGenderLabels,
} from "@/app/management/coaches/coachConstants";
import { CURRENCY_SYMBOL, formatDate, formatMoney } from "@/lib/utils";
import {
  createCoachFormInitialValues,
  getEmploymentTypeForWorkTypes,
} from "@/app/management/coaches/coachFormUtils";
import {
  getCoachBranchNames,
  getUnassignedActivities,
} from "@/app/management/coaches/coachDetailsUtils";

export default function CoachesClient({ initialData }) {
  const {
    search,
    setSearch,
    branchFilter,
    setBranchFilter,
    employmentFilter,
    setEmploymentFilter,
    activityFilter,
    setActivityFilter,
    drawerMode,
    setDrawerMode,
    setSelectedCoachId,
    selectedCoachId,
    formError,
    setFormError,
    isLoading,
    error,
    refetch,
    filteredCoaches,
    stats,
    selectedCoach,
    detailsCoach,
    isFetchingDetails,
    detailsError,
    isCreating,
    isUpdating,
    isDeleting,
    isAddingActivity,
    isDeletingActivity,
    handleCreate,
    handleUpdate,
    handleDelete,
    confirmDelete,
    closeDeleteConfirm,
    deleteConfirmOpen,
    itemToDelete,
    handleAddActivity,
    handleRemoveActivity,
    selectedActivityId,
    setSelectedActivityId,
    getEditInitialValues,
    branches,
    activities,
    closeDrawer,
  } = useCoaches({ initialData });

  const columns = useMemo(
    () => [
      {
        key: "name",
        label: "المدرب",
        align: "start",
        render: (_, coach) => {
          let photoUrl = coach.person?.photo_url || coach.person?.photo;
          if (photoUrl && !photoUrl.startsWith("http") && !photoUrl.startsWith("blob:")) {
            photoUrl = `http://31.70.108.63/${photoUrl.replace(/^\//, "")}`;
          }

          return (
            <div className="flex items-center gap-3 justify-start px-4">
              {photoUrl ? (
                <div
                  className="size-8 rounded-full bg-cover bg-center border border-app-line/30 shrink-0"
                  style={{ backgroundImage: `url(${photoUrl})` }}
                />
              ) : (
                <div className="size-8 rounded-full bg-app-card-soft flex items-center justify-center shrink-0 border border-app-line/30">
                  <span className="text-[11px] font-bold text-app-yellow">
                    {coach.person?.full_name?.charAt(0) || "-"}
                  </span>
                </div>
              )}
              <span className="text-sm font-medium text-white text-right">
                {coach.person?.full_name || "-"}
              </span>
            </div>
          );
        },
      },
      // {
      //   key: "specialization",
      //   label: "التخصص",
      //   align: "center",
      //   render: (_, coach) => (
      //     <span className="text-xs text-app-muted-light">
      //       {coach.details?.specialization || "غير محدد"}
      //     </span>
      //   ),
      // },
      {
        key: "employment_type",
        label: "التوظيف",
        align: "center",
        render: (value) => (
          <span className="text-xs text-app-muted-light">{employmentLabels[value] || value}</span>
        ),
      },
      {
        key: "base_salary",
        label: "الراتب / النسبة",
        align: "center",
        render: (_, coach) => {
          const type = coach.employment_type;
          const salary = formatMoney(coach.base_salary);
          const commission = Number(coach.details?.default_commission_rate || 0);

          if (type === "commission_based" || type === "commission") {
            return (
              <span className="text-xs font-semibold text-app-green" dir="ltr">
                {commission}%
              </span>
            );
          } else if (type === "hybrid") {
            return (
              <div className="flex items-center justify-center gap-1.5 text-xs font-semibold text-app-green">
                <span>{salary}</span>
                <span className="text-app-muted-light">+</span>
                <span dir="ltr">{commission}%</span>
              </div>
            );
          }

          return <span className="text-xs font-semibold text-app-green">{salary}</span>;
        },
      },
      {
        key: "is_active",
        label: "الحالة",
        align: "center",
        render: (value) => (
          <span
            className={`inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold ${
              value ? "bg-app-green/10 text-app-green" : "bg-app-red/10 text-app-red"
            }`}
          >
            {value ? "نشط" : "غير نشط"}
          </span>
        ),
      },
      {
        key: "actions",
        label: "الإجراءات",
        align: "center",
        render: (_, coach) => (
          <RowActions
            disabled={isDeleting}
            editHref={`/management/coaches/create?mode=edit&id=${coach.id}`}
            onDelete={() => handleDelete(coach)}
          />
        ),
      },
    ],
    [isDeleting, handleDelete, setFormError, setSelectedCoachId, setDrawerMode],
  );

  const editInitialValues = useMemo(() => {
    return getEditInitialValues();
  }, [selectedCoach]);

  const branchOptions = useMemo(
    () => [
      { value: "all", label: "كل الفروع" },
      ...branches.map((b) => ({ value: String(b.id), label: b.name })),
    ],
    [branches],
  );

  const employmentOptions = useMemo(
    () => [{ value: "all", label: "كل أنواع التوظيف" }, ...employmentTypes],
    [],
  );

  const activityOptions = useMemo(
    () => [
      { value: "all", label: "كل الأنشطة" },
      ...activities.map((a) => ({ value: String(a.id), label: a.name })),
    ],
    [activities],
  );

  return (
    <div className="space-y-6" dir="rtl">
      <PageHeader
        eyebrow="إدارة النظام"
        title="إدارة المدربين"
        subtitle="عرض قائمة الكوادر التدريبية، إحصاءات الأجور، تعيين المهام والرياضات لكل كوتش."
        action={
          <Button
            href="/management/coaches/create"
            icon={<PlusIcon className="size-4" style={{ color: "#000000" }} />}
            style={{ color: "#000000" }}
          >
            إضافة مدرب
          </Button>
        }
      />

      <StatsGrid items={stats} />

      <DataTable
        title="قائمة المدربين والكفاءات"
        columns={columns}
        rows={filteredCoaches}
        minWidth="800px"
        tableColumns={TABLE_GRID_COLUMNS}
        showAdd={false}
        showSearch={false}
        showFilter={false}
        showExport={false}
        isLoading={isLoading}
        emptyMessage={
          error ? (
            <div className="space-y-3 text-center">
              <p className="text-app-red">تعذر تحميل بيانات المدربين.</p>
              <Button tone="outline" className="h-9 px-3 text-xs" onClick={refetch}>
                إعادة المحاولة
              </Button>
            </div>
          ) : (
            "لا يوجد مدربون مسجلون حالياً."
          )
        }
        rowClassName="gap-2 px-3 py-4"
        headerClassName="gap-2 px-3"
        totalPages={0}
        onRowClick={(coach) => {
          setSelectedCoachId(coach.id);
          setDrawerMode("details");
        }}
        getRowKey={(coach) => coach.id}
        toolbarActions={
          <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:flex-wrap">
            <label className="relative block w-full sm:w-80 md:w-96">
              <SearchIcon className="pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2 text-app-muted-light" />
              <input
                className="app-input h-10 w-full bg-app-card-soft ps-9 pe-3 text-right text-sm text-white outline-none transition focus:border-app-yellow/70"
                value={search}
                onChange={(event) => setSearch(event.target.value)}
                placeholder="البحث باسم المدرب أو التخصص..."
                type="search"
              />
            </label>

            <Dropdown
              className="min-w-48 bg-app-card-soft border-app-line text-white"
              icon={FilterIcon}
              value={branchFilter}
              options={branchOptions}
              onChange={setBranchFilter}
            />

            <Dropdown
              className="min-w-48 bg-app-card-soft border-app-line text-white"
              icon={FilterIcon}
              value={activityFilter}
              options={activityOptions}
              onChange={setActivityFilter}
            />

            <Dropdown
              className="min-w-48 bg-app-card-soft border-app-line text-white"
              icon={FilterIcon}
              value={employmentFilter}
              options={employmentOptions}
              onChange={setEmploymentFilter}
            />
          </div>
        }
        toolbarMeta={
          <p className="text-sm text-app-muted-light">
            النتائج:{" "}
            <span className="font-medium text-app-text">
              {filteredCoaches.length.toLocaleString("ar")}
            </span>
          </p>
        }
      />

      <Drawer
        open={drawerMode === "details"}
        onClose={closeDrawer}
        title="تفاصيل المدرب"
        subtitle={detailsCoach?.person?.full_name || selectedCoach?.person?.full_name || ""}
      >
        <CoachDetails
          coach={detailsCoach || selectedCoach}
          branches={branches}
          allActivities={activities}
          isLoading={isFetchingDetails}
          error={detailsError}
          selectedActivityId={selectedActivityId}
          setSelectedActivityId={setSelectedActivityId}
          onAddActivity={handleAddActivity}
          onRemoveActivity={handleRemoveActivity}
          isAddingActivity={isAddingActivity}
          isRemovingActivity={isDeletingActivity}
        />
      </Drawer>

      <ConfirmDialog
        open={deleteConfirmOpen}
        onClose={closeDeleteConfirm}
        onConfirm={confirmDelete}
        title="تأكيد حذف المدرب"
        message={`هل أنت متأكد من رغبتك في حذف المدرب "${itemToDelete ? itemToDelete.person?.full_name : ""}"؟ لا يمكن التراجع عن هذا الإجراء.`}
        isLoading={isDeleting}
      />
    </div>
  );
}
