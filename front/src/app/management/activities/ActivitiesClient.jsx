"use client";

import { useMemo, useState } from "react";
import PageHeader from "@/components/common/PageHeader";
import Button from "@/components/ui/Button";
import DataTable from "@/components/ui/DataTable";
import Drawer from "@/components/ui/Drawer";
import RowActions from "@/components/ui/RowActions";
import SkeletonPage from "@/components/ui/Skeleton";
import StatsGrid from "@/components/ui/StatsGrid";
import Dropdown from "@/components/ui/Dropdown";
import { PlusIcon } from "@/components/icons/Icons";
import ConfirmDialog from "@/components/ui/ConfirmDialog";
import DetailItem from "@/components/ui/DetailItem";
import SearchInput from "@/components/ui/SearchInput";
import { Field, TextAreaField } from "@/components/forms/FormControls";
import { useActivities } from "./useActivities";
import { formatDate, formatLocalizedName } from "@/lib/utils";
import { genderLabels } from "@/lib/constants";
import { activitySchema } from "@/lib/validations/activitiesSchema";
import Checkbox from "@/components/ui/Checkbox";
import { useGetBranchShiftsQuery } from "@/lib/api/branchesApi";

const TABLE_GRID_COLUMNS =
  "minmax(180px,1.2fr) minmax(200px,1.8fr) 120px 120px 100px";

const initialForm = {
  name: "",
  description: "",
  gender_allowed: "mixed",
  branch_id: "",
  activity_type_id: "",
  is_active: true,
  shifts: [],
};

const activityName = (act) => formatLocalizedName(act?.name);

function ActivityDetails({ activity, isLoading, error }) {
  if (isLoading) {
    return (
      <SkeletonPage
        blocks={[{ type: "details", sections: 2, itemsPerSection: 3 }]}
      />
    );
  }

  if (error) {
    return (
      <div className="rounded-xl border border-app-red/30 bg-app-red/10 p-5 text-right text-sm text-app-red">
        تعذر تحميل تفاصيل النشاط.
      </div>
    );
  }

  if (!activity) {
    return (
      <div className="rounded-xl border border-app-line bg-app-card-soft/60 p-6 text-center text-sm text-app-muted-light">
        لا توجد تفاصيل لهذا النشاط.
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="rounded-xl border border-app-line bg-app-card-soft/70 p-4 text-right">
        <h3 className="text-lg font-medium text-app-text">
          {activityName(activity)}
        </h3>
        <p className="mt-1 text-xs text-app-muted-light">
          رمز النشاط: #{activity.id}
        </p>
      </div>

      <section className="grid gap-3 sm:grid-cols-2">
        <DetailItem
          label="الجمهور المسموح"
          value={
            genderLabels[activity.gender_allowed] || activity.gender_allowed
          }
          tone="yellow"
        />
        <DetailItem
          label="الحالة"
          value={activity.is_active ? "نشط" : "غير نشط"}
          tone={activity.is_active ? "green" : "default"}
        />
        <DetailItem
          label="نوع الفئة / التصنيف"
          value={activity.activity_type?.name || "-"}
        />
        <DetailItem
          label="تاريخ الإنشاء"
          value={formatDate(activity.created_at)}
        />
      </section>

      {activity.description && (
        <div className="rounded-xl border border-app-line bg-app-card-soft/50 p-4 text-right">
          <h4 className="text-xs text-app-muted-light font-medium">الوصف</h4>
          <p className="mt-2 text-sm text-app-text leading-relaxed">
            {activity.description}
          </p>
        </div>
      )}
    </div>
  );
}

const daysOfWeek = {
  0: "الأحد",
  1: "الإثنين",
  2: "الثلاثاء",
  3: "الأربعاء",
  4: "الخميس",
  5: "الجمعة",
  6: "السبت",
};

export function ActivityForm({
  mode,
  initialValues = initialForm,
  branches = [],
  activityTypes = [],
  onSubmit,
  onCancel,
  isLoading,
  errorMessage,
  formId,
  showFooterActions = true,
  formClassName = "space-y-4",
}) {
  const [form, setForm] = useState(initialValues);
  const [errors, setErrors] = useState({});

  const branchIdNum = Number(form.branch_id);
  const { data: shiftsResponse, isFetching: isLoadingShifts } = useGetBranchShiftsQuery(
    branchIdNum,
    { skip: !branchIdNum }
  );
  const branchShifts = useMemo(() => {
    return Array.isArray(shiftsResponse?.data) ? shiftsResponse.data : [];
  }, [shiftsResponse]);

  const showShifts = Number(form.activity_type_id) === 4 || Number(form.activity_type_id) === 5;

  function updateField(field, value) {
    setForm((current) => {
      const updated = { ...current, [field]: value };
      if (field === "branch_id") {
        updated.shifts = [];
      }
      return updated;
    });
    if (errors[field]) setErrors((current) => ({ ...current, [field]: null }));
  }

  function handleSubmit(event) {
    event.preventDefault();
    const validationData = {
      name: (form.name || "").trim(),
      description: (form.description || "").trim() || null,
      gender_allowed: form.gender_allowed || "mixed",
      branch_id: form.branch_id,
      activity_type_id: form.activity_type_id,
      is_active: !!form.is_active,
      shifts: form.shifts || [],
    };

    const result = activitySchema.safeParse(validationData);
    if (!result.success) {
      const formattedErrors = {};
      result.error.issues.forEach((issue) => {
        formattedErrors[issue.path.join("_")] = issue.message;
      });
      setErrors(formattedErrors);
      return;
    }

    setErrors({});
    onSubmit({
      name: (form.name || "").trim(),
      description: (form.description || "").trim() || null,
      gender_allowed: form.gender_allowed || "mixed",
      branch_id: Number(form.branch_id),
      activity_type_id: Number(form.activity_type_id),
      is_active: !!form.is_active,
      shifts: showShifts ? (form.shifts || []).map(Number) : [],
    });
  }

  return (
    <form
      id={formId}
      noValidate
      onSubmit={handleSubmit}
      className={formClassName}
      dir="rtl"
    >
      <Field
        label="اسم النشاط"
        value={form.name || ""}
        onChange={(event) => updateField("name", event.target.value)}
        placeholder="مثال: صالة حديد حرة أو يوغا"
        required
        type="text"
        error={errors.name}
      />

      <label className="block text-right text-sm text-app-muted-light">
        الفرع التابع له
        <Dropdown
          className="mt-2 text-white"
          buttonClassName="bg-app-card-soft h-11"
          value={form.branch_id || ""}
          onChange={(val) => updateField("branch_id", val)}
          options={branches.map((b) => {
            const label = typeof b.name === "string" ? b.name : b.name?.ar || b.name?.en || "-";
            return { value: String(b.id), label };
          })}
          placeholder="اختر الفرع"
          error={errors.branch_id}
        />
      </label>

      <label className="block text-right text-sm text-app-muted-light">
        نوع الفئة / التصنيف
        <Dropdown
          className="mt-2 text-white"
          buttonClassName="bg-app-card-soft h-11"
          value={form.activity_type_id || ""}
          onChange={(val) => updateField("activity_type_id", val)}
          options={activityTypes.map((type) => {
            const label = typeof type.name === "string" ? type.name : type.name?.ar || type.name?.en || "-";
            return { value: String(type.id), label };
          })}
          placeholder="اختر نوع الفئة"
          error={errors.activity_type_id}
        />
      </label>

      {showShifts && (
        <div className="block text-right text-sm text-app-muted-light">
          الورديات / الشفتات المتاحة
          <div className="mt-2 grid grid-cols-2 gap-3 p-3 bg-app-card-soft rounded-lg border border-app-line max-h-48 overflow-y-auto">
            {isLoadingShifts ? (
              <p className="text-xs text-app-muted-light text-center py-2 col-span-2">جاري تحميل الورديات...</p>
            ) : branchShifts.length === 0 ? (
              <p className="text-xs text-app-muted-light text-center py-2 col-span-2">لا توجد ورديات مسجلة لهذا الفرع</p>
            ) : (
              branchShifts.map((shift) => {
                const isChecked = form.shifts?.includes(shift.id);
                const dayName = daysOfWeek[shift.day_of_week] || "يوم غير معروف";
                const startTime = shift.start_time ? shift.start_time.slice(0, 5) : "";
                const endTime = shift.end_time ? shift.end_time.slice(0, 5) : "";
                const gender = genderLabels[shift.gender_allowed] || shift.gender_allowed || "مختلط";
                const label = `${dayName} | من ${startTime} إلى ${endTime} (${gender})`;
                
                return (
                  <Checkbox
                    key={shift.id}
                    label={label}
                    checked={isChecked}
                    onChange={() => {
                      const id = shift.id;
                      const newShifts = isChecked
                        ? (form.shifts || []).filter((x) => x !== id)
                        : [...(form.shifts || []), id];
                      updateField("shifts", newShifts);
                    }}
                  />
                );
              })
            )}
          </div>
        </div>
      )}

      <TextAreaField
        label="الوصف"
        value={form.description || ""}
        onChange={(event) => updateField("description", event.target.value)}
        placeholder="أدخل وصفاً مختصراً للنشاط والتمارين..."
        error={errors.description}
      />

      <label className="block text-right text-sm text-app-muted-light">
        الجمهور المستهدف
        <Dropdown
          className="mt-2 text-white"
          buttonClassName="bg-app-card-soft h-11"
          value={form.gender_allowed || "mixed"}
          onChange={(val) => updateField("gender_allowed", val)}
          options={[
            { value: "mixed", label: "مختلط" },
            { value: "male", label: "ذكور فقط" },
            { value: "female", label: "إناث فقط" },
          ]}
          placeholder="اختر الفئة المسموح بها"
          error={errors.gender_allowed}
        />
      </label>

      <div className="flex items-center gap-3 pt-2">
        <label className="relative inline-flex cursor-pointer items-center">
          <input
            type="checkbox"
            checked={!!form.is_active}
            onChange={(e) => updateField("is_active", e.target.checked)}
            className="peer sr-only"
          />
          <div className="peer h-6 w-11 rounded-full bg-app-line after:absolute after:top-[2px] after:right-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all peer-checked:bg-app-yellow peer-checked:after:-translate-x-[18px]"></div>
        </label>
        <span className="text-sm font-medium text-white">
          نشط في النظام
        </span>
      </div>

      {errorMessage && (
        <p className="rounded-xl border border-app-red/30 bg-app-red/10 p-3 text-center text-xs text-app-red">
          {errorMessage}
        </p>
      )}

      <div
        className={`${showFooterActions ? "flex" : "entry-form-actions-hidden"} gap-3 pt-2`}
      >
        <Button
          type="button"
          tone="outline"
          className="h-11 flex-1"
          onClick={onCancel}
        >
          إلغاء
        </Button>
        <Button type="submit" className="h-11 flex-1" loading={isLoading}>
          {mode === "edit" ? "حفظ التعديل" : "إنشاء النشاط"}
        </Button>
      </div>
    </form>
  );
}

export default function ActivitiesClient() {
  const {
    search,
    setSearch,
    drawerMode,
    setDrawerMode,
    setSelectedActivityId,
    selectedActivityId,
    formError,
    setFormError,
    isLoading,
    error,
    refetch,
    filteredActivities,
    stats,
    selectedActivity,
    detailsActivity,
    isFetchingDetails,
    detailsError,
    isCreating,
    isUpdating,
    isDeleting,
    handleCreate,
    handleUpdate,
    handleDelete,
    confirmDelete,
    closeDeleteConfirm,
    deleteConfirmOpen,
    itemToDelete,
    getEditInitialValues,
    closeDrawer,
    branches,
    activityTypes,
  } = useActivities();

  const columns = useMemo(
    () => [
      {
        key: "name",
        label: "النشاط",
        align: "center",
        render: (_, act) => (
          <span className="text-sm font-medium text-app-text">
            {activityName(act)}
          </span>
        ),
      },
      {
        key: "description",
        label: "الوصف",
        align: "center",
        render: (value) => (
          <span className="text-xs text-app-muted-light truncate block max-w-64 text-center">
            {value || "-"}
          </span>
        ),
      },
      {
        key: "gender_allowed",
        label: "الفئة",
        align: "center",
        render: (value) => (
          <span className="text-xs text-app-muted-light">
            {genderLabels[value] || value}
          </span>
        ),
      },
      {
        key: "is_active",
        label: "الحالة",
        align: "center",
        render: (value) => (
          <span
            className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${
              value
                ? "bg-app-green/10 text-app-green"
                : "bg-app-red/10 text-app-red"
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
        render: (_, act) => (
          <RowActions
            disabled={isDeleting}
            editHref={`/management/activities/create?mode=edit&id=${act.id}`}
            onDelete={() => handleDelete(act)}
          />
        ),
      },
    ],
    [isDeleting, handleDelete],
  );

  const editInitialValues = useMemo(() => {
    return getEditInitialValues();
  }, [selectedActivity]);

  return (
    <div className="space-y-6" dir="rtl">
      <PageHeader
        eyebrow="إدارة النظام"
        title="الأنشطة الرياضية"
        subtitle="عرض وتعديل وتصنيف الرياضات والأنشطة الجماعية والتدريبات الخاصة في النادي."
        action={
          <Button
            href="/management/activities/create"
            icon={<PlusIcon className="size-4" style={{ color: "#000000" }} />}
            style={{ color: "#000000" }}
          >
            إضافة نشاط
          </Button>
        }
      />

      <StatsGrid items={stats} />

      <DataTable
        title="قائمة الأنشطة والرياضات"
        columns={columns}
        rows={filteredActivities}
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
              <p className="text-app-red">تعذر تحميل قائمة الأنشطة الرياضية.</p>
              <Button
                tone="outline"
                className="h-9 px-3 text-xs"
                onClick={refetch}
              >
                إعادة المحاولة
              </Button>
            </div>
          ) : (
            "لا توجد أنشطة مسجلة حالياً."
          )
        }
        rowClassName="gap-2 px-3 py-4"
        headerClassName="gap-2 px-3"
        totalPages={0}
        onRowClick={(act) => {
          setSelectedActivityId(act.id);
          setDrawerMode("details");
        }}
        getRowKey={(act) => act.id}
        toolbarActions={
          <SearchInput
            value={search}
            onChange={(event) => setSearch(event.target.value)}
            placeholder="البحث باسم النشاط أو الوصف..."
            className="min-w-80"
          />
        }
        toolbarMeta={
          <p className="text-sm text-app-muted-light">
            النتائج:{" "}
            <span className="font-medium text-app-text">
              {filteredActivities.length.toLocaleString("ar")}
            </span>
          </p>
        }
      />

      <Drawer
        open={drawerMode === "edit"}
        onClose={closeDrawer}
        title="تعديل بيانات النشاط"
        subtitle={selectedActivity ? activityName(selectedActivity) : ""}
      >
        {editInitialValues && (
          <ActivityForm
            key={selectedActivityId || "edit"}
            mode="edit"
            initialValues={editInitialValues}
            branches={branches}
            activityTypes={activityTypes}
            onSubmit={handleUpdate}
            onCancel={closeDrawer}
            isLoading={isUpdating}
            errorMessage={formError}
          />
        )}
      </Drawer>

      <Drawer
        open={drawerMode === "details"}
        onClose={closeDrawer}
        title="تفاصيل النشاط"
        subtitle={
          detailsActivity
            ? activityName(detailsActivity)
            : selectedActivity
              ? activityName(selectedActivity)
              : ""
        }
      >
        <ActivityDetails
          activity={detailsActivity || selectedActivity}
          isLoading={isFetchingDetails}
          error={detailsError}
        />
      </Drawer>

      <ConfirmDialog
        open={deleteConfirmOpen}
        onClose={closeDeleteConfirm}
        onConfirm={confirmDelete}
        title="تأكيد حذف النشاط"
        message={`هل أنت متأكد من رغبتك في حذف نشاط "${itemToDelete ? activityName(itemToDelete) : ""}"؟ لا يمكن التراجع عن هذا الإجراء.`}
        isLoading={isDeleting}
      />
    </div>
  );
}
