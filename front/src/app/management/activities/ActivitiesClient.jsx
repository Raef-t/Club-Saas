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

const TABLE_GRID_COLUMNS = "minmax(180px,1.2fr) minmax(200px,1.8fr) 110px 130px 90px 100px";

const initialForm = {
  name_ar: "",
  name_en: "",
  description: "",
  type: "group_class",
  default_capacity: "20",
  is_private_equipment: "0",
  gender_allowed: "mixed",
};

const activityName = formatLocalizedName;


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
          label="نوع النشاط"
          value={activity.type === "group_class" ? "حصة جماعية" : "تدريب فردي / خاص"}
        />
        <DetailItem
          label="الجمهور المسموح"
          value={genderLabels[activity.gender_allowed] || activity.gender_allowed}
          tone="yellow"
        />
        <DetailItem
          label="السعة الافتراضية"
          value={activity.default_capacity ? `${activity.default_capacity} شخص` : "-"}
        />
        <DetailItem
          label="طبيعة الأجهزة"
          value={activity.is_private_equipment ? "تدريب خاص / أجهزة خاصة" : "أجهزة عامة"}
          tone={activity.is_private_equipment ? "yellow" : "default"}
        />
        <DetailItem label="تاريخ الإنشاء" value={formatDate(activity.created_at)} />
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

function ActivityForm({
  mode,
  initialValues = initialForm,
  onSubmit,
  onCancel,
  isLoading,
  errorMessage,
}) {
  const [form, setForm] = useState(initialValues);
  const [errors, setErrors] = useState({});

  function updateField(field, value) {
    setForm((current) => ({ ...current, [field]: value }));
    if (errors[field]) setErrors((current) => ({ ...current, [field]: null }));
  }

  function handleSubmit(event) {
    event.preventDefault();
    const data = {
      name: {
        ar: form.name_ar.trim(),
        en: form.name_en.trim(),
      },
      description: form.description.trim() || null,
      type: form.type,
      default_capacity: Number(form.default_capacity) || 20,
      is_private_equipment: form.is_private_equipment === "1",
      gender_allowed: form.gender_allowed,
    };
    
    const result = activitySchema.safeParse(data);
    if (!result.success) {
      const formattedErrors = {};
      result.error.issues.forEach(issue => {
        formattedErrors[issue.path.join('_')] = issue.message;
      });
      setErrors(formattedErrors);
      return;
    }
    
    setErrors({});
    onSubmit(data);
  }

  return (
    <form noValidate onSubmit={handleSubmit} className="space-y-4" dir="rtl">
      <Field
        label="اسم النشاط بالعربية"
        value={form.name_ar}
        onChange={(event) => updateField("name_ar", event.target.value)}
        placeholder="مثال: صالة حديد حرة"
        required
        type="text"
       error={errors.name_ar}
        />

      <Field
        label="اسم النشاط بالإنجليزية"
        value={form.name_en}
        onChange={(event) => updateField("name_en", event.target.value)}
        placeholder="Open Gym"
        dir="ltr"
        required
        type="text"
       error={errors.name_en}
        />

      <TextAreaField
        label="الوصف"
        value={form.description}
        onChange={(event) => updateField("description", event.target.value)}
        placeholder="أدخل وصفاً مختصراً للنشاط والتمارين..."
       error={errors.description}
        />

      <label className="block text-right text-sm text-app-muted-light">
        نوع النشاط
        <Dropdown
          className="mt-2 text-white"
          buttonClassName="bg-app-card-soft h-11"
          value={form.type}
          onChange={(val) => updateField("type", val)}
          options={[
            { value: "group_class", label: "حصة تدريبية جماعية" },
            { value: "private_session", label: "تدريب خاص / فردي" },
          ]}
          placeholder="اختر النوع"
         error={errors.type}
          />
      </label>

      <label className="block text-right text-sm text-app-muted-light">
        نوع الأجهزة المشمولة
        <Dropdown
          className="mt-2 text-white"
          buttonClassName="bg-app-card-soft h-11"
          value={form.is_private_equipment}
          onChange={(val) => updateField("is_private_equipment", val)}
          options={[
            { value: "0", label: "أجهزة عامة ومفتوحة للكل" },
            { value: "1", label: "أجهزة خاصة / تدريب خاص" },
          ]}
          placeholder="نوع التجهيز"
         error={errors.is_private_equipment}
          />
      </label>

      <label className="block text-right text-sm text-app-muted-light">
        الجمهور المستهدف
        <Dropdown
          className="mt-2 text-white"
          buttonClassName="bg-app-card-soft h-11"
          value={form.gender_allowed}
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

      <Field
        label="السعة الافتراضية للنشاط"
        value={form.default_capacity}
        onChange={(event) => updateField("default_capacity", event.target.value)}
        type="number"
        min="1"
        required
       error={errors.default_capacity}
        />

      {errorMessage && (
        <p className="rounded-xl border border-app-red/30 bg-app-red/10 p-3 text-center text-xs text-app-red">
          {errorMessage}
        </p>
      )}

      <div className="flex gap-3 pt-2">
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
        key: "is_private_equipment",
        label: "نوع التجهيز",
        align: "center",
        render: (value) => (
          <span className="text-xs text-app-muted-light">
            {value ? "تدريب / جهاز خاص" : "عام"}
          </span>
        ),
      },
      {
        key: "default_capacity",
        label: "السعة",
        align: "center",
        render: (value) => (
          <span className="text-xs text-app-text font-medium">
            {value ? `${value} شخص` : "-"}
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
            onEdit={() => {
              setFormError("");
              setSelectedActivityId(act.id);
              setDrawerMode("edit");
            }}
            onDelete={() => handleDelete(act)}
          />
        ),
      },
    ],
    [isDeleting, handleDelete, setFormError, setSelectedActivityId, setDrawerMode],
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
            icon={<PlusIcon className="size-4" />}
            onClick={() => {
              setFormError("");
              setDrawerMode("create");
            }}
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
        open={drawerMode === "create"}
        onClose={closeDrawer}
        title="إضافة نشاط جديد"
        subtitle="أدخل تفاصيل النشاط والتمارين المتاحة"
      >
        <ActivityForm
          mode="create"
          onSubmit={handleCreate}
          onCancel={closeDrawer}
          isLoading={isCreating}
          errorMessage={formError}
        />
      </Drawer>

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
        subtitle={detailsActivity ? activityName(detailsActivity) : (selectedActivity ? activityName(selectedActivity) : "")}
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
