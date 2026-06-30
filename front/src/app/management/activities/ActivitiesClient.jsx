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
import { PlusIcon, SearchIcon } from "@/components/icons/Icons";
import ConfirmDialog from "@/components/ui/ConfirmDialog";
import { useActivities } from "./useActivities";

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

const genderLabels = {
  mixed: "مختلط",
  male: "ذكور",
  female: "إناث",
};

function activityName(act) {
  if (!act?.name) return "-";
  if (typeof act.name === "string") return act.name;
  return act.name.ar || act.name.en || "-";
}

function formatDate(value) {
  if (!value) return "-";
  const date = new Date(value);
  if (isNaN(date.getTime())) return "-";

  return new Intl.DateTimeFormat("ar", {
    year: "numeric",
    month: "short",
    day: "numeric",
  }).format(date);
}

function DetailItem({ label, value, tone = "default" }) {
  const toneClass =
    tone === "green"
      ? "text-app-green"
      : tone === "red"
        ? "text-app-red"
        : tone === "yellow"
          ? "text-app-yellow"
          : "text-app-text";

  return (
    <div className="rounded-lg border border-app-line bg-app-card-soft/70 p-3 text-right">
      <p className="text-[11px] text-app-muted-light">{label}</p>
      <p className={`mt-1 text-sm font-medium ${toneClass}`}>
        {value ?? "-"}
      </p>
    </div>
  );
}

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

  function updateField(field, value) {
    setForm((current) => ({ ...current, [field]: value }));
  }

  function handleSubmit(event) {
    event.preventDefault();
    onSubmit({
      name: {
        ar: form.name_ar.trim(),
        en: form.name_en.trim(),
      },
      description: form.description.trim() || null,
      type: form.type,
      default_capacity: Number(form.default_capacity) || 20,
      is_private_equipment: form.is_private_equipment === "1",
      gender_allowed: form.gender_allowed,
    });
  }

  return (
    <form onSubmit={handleSubmit} className="space-y-4" dir="rtl">
      <label className="block text-right text-sm text-app-muted-light">
        اسم النشاط بالعربية
        <input
          value={form.name_ar}
          onChange={(event) => updateField("name_ar", event.target.value)}
          className="app-input mt-2 h-11 w-full px-3 text-right outline-none focus:border-app-yellow/70 bg-app-card-soft text-white"
          placeholder="مثال: صالة حديد حرة"
          required
        />
      </label>

      <label className="block text-right text-sm text-app-muted-light">
        اسم النشاط بالإنجليزية
        <input
          value={form.name_en}
          onChange={(event) => updateField("name_en", event.target.value)}
          className="app-input mt-2 h-11 w-full px-3 text-left outline-none focus:border-app-yellow/70 bg-app-card-soft text-white"
          placeholder="Open Gym"
          dir="ltr"
          required
        />
      </label>

      <label className="block text-right text-sm text-app-muted-light">
        الوصف
        <textarea
          value={form.description}
          onChange={(event) => updateField("description", event.target.value)}
          className="app-input mt-2 h-24 w-full p-3 text-right outline-none focus:border-app-yellow/70 bg-app-card-soft text-white resize-none"
          placeholder="أدخل وصفاً مختصراً للنشاط والتمارين..."
        />
      </label>

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
        />
      </label>

      <label className="block text-right text-sm text-app-muted-light">
        السعة الافتراضية للنشاط
        <input
          value={form.default_capacity}
          onChange={(event) => updateField("default_capacity", event.target.value)}
          className="app-input mt-2 h-11 w-full px-3 text-right outline-none focus:border-app-yellow/70 bg-app-card-soft text-white"
          type="number"
          min="1"
          required
        />
      </label>

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
          <label className="relative block min-w-80">
            <SearchIcon className="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-app-muted-light" />
            <input
              className="app-input h-10 w-full pr-9 pl-3 text-sm outline-none transition focus:border-app-yellow/70 bg-app-card-soft text-white"
              value={search}
              onChange={(event) => setSearch(event.target.value)}
              placeholder="البحث باسم النشاط أو الوصف..."
              type="search"
            />
          </label>
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
