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
import { PlusIcon, SearchIcon, FilterIcon, TrashIcon } from "@/components/icons/Icons";
import ConfirmDialog from "@/components/ui/ConfirmDialog";
import { useCoaches } from "./useCoaches";

const TABLE_GRID_COLUMNS = "minmax(160px,1.2fr) 120px 140px 120px 100px 100px";
const CURRENCY_SYMBOL = "ل.س";

const employmentTypes = [
  { value: "fixed_salary", label: "راتب ثابت" },
  { value: "commission", label: "عمولات فقط" },
  { value: "hourly", label: "أجر ساعي" },
  { value: "hybrid", label: "هجين (ثابت + عمولة)" },
];

const employmentLabels = {
  fixed_salary: "راتب ثابت",
  commission: "عمولات فقط",
  hourly: "أجر ساعي",
  hybrid: "نظام هجين",
};

const genderLabels = {
  male: "ذكر",
  female: "أنثى",
};

function formatMoney(value) {
  const num = Number(value) || 0;
  return `${num.toLocaleString("en-US")} ${CURRENCY_SYMBOL}`;
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
      <p className={`mt-1 truncate text-sm font-medium ${toneClass}`}>
        {value ?? "-"}
      </p>
    </div>
  );
}

function CoachDetails({
  coach,
  branches = [],
  allActivities = [],
  isLoading,
  error,
  selectedActivityId,
  setSelectedActivityId,
  onAddActivity,
  onRemoveActivity,
  isAddingActivity,
  isRemovingActivity,
}) {
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
        تعذر تحميل تفاصيل المدرب.
      </div>
    );
  }

  if (!coach) {
    return (
      <div className="rounded-xl border border-app-line bg-app-card-soft/60 p-6 text-center text-sm text-app-muted-light">
        لا توجد تفاصيل متوفرة.
      </div>
    );
  }

  const branchName =
    branches.find((b) => b.id === coach.branch_id)?.name || `فرع #${coach.branch_id}`;

  const coachActivities = coach.activities || [];
  const unassignedActivities = allActivities.filter(
    (act) => !coachActivities.some((ca) => ca.id === act.id),
  );

  return (
    <div className="space-y-6">
      {/* Basic Profile */}
      <div className="rounded-xl border border-app-line bg-app-card-soft/70 p-4 text-right flex items-center justify-between gap-4">
        <div>
          <h3 className="text-lg font-medium text-white">
            {coach.person?.full_name || "-"}
          </h3>
          <p className="mt-1 text-xs text-app-muted-light">
            كود الموظف: #{coach.id} | دور: {coach.role}
          </p>
        </div>
        <span
          className={`rounded px-2.5 py-1 text-xs font-semibold ${
            coach.is_active
              ? "bg-app-green/10 text-app-green"
              : "bg-app-red/10 text-app-red"
          }`}
        >
          {coach.is_active ? "نشط" : "غير نشط"}
        </span>
      </div>

      {/* Info Grid */}
      <section className="grid gap-3 sm:grid-cols-2">
        <DetailItem label="الفرع" value={branchName} tone="yellow" />
        <DetailItem label="التخصص" value={coach.details?.specialization || "غير محدد"} />
        <DetailItem label="سنوات الخبرة" value={coach.details?.experience_years ? `${coach.details.experience_years} سنوات` : "-"} />
        <DetailItem label="الجنس" value={genderLabels[coach.person?.gender] || coach.person?.gender} />
        <DetailItem label="تاريخ الميلاد" value={formatDate(coach.person?.dob)} />
        <DetailItem label="العمر" value={coach.person?.age ? `${coach.person.age} سنة` : "-"} />
        <DetailItem label="نوع التوظيف" value={employmentLabels[coach.employment_type] || coach.employment_type} />
        <DetailItem label="الراتب الأساسي" value={formatMoney(coach.base_salary)} tone="green" />
        <DetailItem label="العنوان" value={coach.person?.address} />
        <DetailItem label="البريد الإلكتروني" value={coach.person?.email} />
        <DetailItem label="رقم الهوية الوطنية" value={coach.person?.national_id} />
        <DetailItem label="تاريخ الانضمام" value={formatDate(coach.created_at)} />
      </section>

      {/* Activities Section */}
      <div className="rounded-xl border border-app-line bg-app-card-soft/50 p-4 text-right space-y-4">
        <h4 className="text-sm font-semibold text-white">الأنشطة والرياضات المنسوبة للمدرب</h4>

        {coachActivities.length === 0 ? (
          <p className="text-xs text-app-muted-light">لم يتم إسناد أي أنشطة رياضية لهذا المدرب بعد.</p>
        ) : (
          <div className="space-y-2">
            {coachActivities.map((act) => (
              <div
                key={act.id}
                className="flex items-center justify-between p-2 rounded-lg bg-black/35 border border-app-line text-sm"
              >
                <button
                  type="button"
                  onClick={() => onRemoveActivity(act.id)}
                  disabled={isRemovingActivity}
                  className="text-app-red hover:bg-app-red/10 p-1.5 rounded transition disabled:opacity-50"
                  title="إلغاء إسناد النشاط"
                >
                  <TrashIcon className="size-4" />
                </button>
                <span className="font-medium text-app-text">
                  {typeof act.name === "string" ? act.name : act.name?.ar || act.name?.en || "-"}
                </span>
              </div>
            ))}
          </div>
        )}

        {/* Add Activity Controls */}
        {unassignedActivities.length > 0 && (
          <div className="pt-2 border-t border-app-line flex items-end gap-3">
            <div className="flex-1">
              <label className="block text-xs text-app-muted-light mb-1.5">إسناد نشاط جديد</label>
              <Dropdown
                className="text-white bg-black/40 rounded-lg text-sm"
                buttonClassName="h-10 border-app-line"
                value={selectedActivityId}
                onChange={setSelectedActivityId}
                options={unassignedActivities.map((act) => ({
                  value: act.id,
                  label: typeof act.name === "string" ? act.name : act.name?.ar || act.name?.en || "",
                }))}
                placeholder="اختر نشاطاً رياضياً..."
              />
            </div>
            <Button
              type="button"
              className="h-10 px-4 text-sm font-medium"
              disabled={!selectedActivityId || isAddingActivity}
              loading={isAddingActivity}
              onClick={onAddActivity}
            >
              إسناد
            </Button>
          </div>
        )}
      </div>
    </div>
  );
}

function CoachCreateForm({
  branches = [],
  onSubmit,
  onCancel,
  isLoading,
  errorMessage,
}) {
  const [form, setForm] = useState({
    full_name: "",
    gender: "male",
    dob: "",
    phone: "",
    email: "",
    address: "",
    branch_id: branches[0]?.id ? String(branches[0].id) : "",
    specialization: "",
    experience_years: "0",
    employment_type: "fixed_salary",
    base_salary: "3000",
  });

  function updateField(field, value) {
    setForm((current) => ({ ...current, [field]: value }));
  }

  function handleSubmit(event) {
    event.preventDefault();
    onSubmit({
      full_name: form.full_name.trim(),
      gender: form.gender,
      dob: form.dob || null,
      phone: form.phone.trim() || null,
      email: form.email.trim() || null,
      address: form.address.trim() || null,
      branch_id: Number(form.branch_id),
      specialization: form.specialization.trim() || null,
      experience_years: Number(form.experience_years) || 0,
      employment_type: form.employment_type,
      base_salary: Number(form.base_salary) || 0,
    });
  }

  return (
    <form onSubmit={handleSubmit} className="space-y-4" dir="rtl">
      <label className="block text-right text-sm text-app-muted-light">
        الاسم الكامل للمدرب
        <input
          value={form.full_name}
          onChange={(event) => updateField("full_name", event.target.value)}
          className="app-input mt-2 h-11 w-full px-3 text-right outline-none focus:border-app-yellow/70 bg-app-card-soft text-white"
          placeholder="الاسم الثلاثي للمدرب"
          required
        />
      </label>

      <div className="grid grid-cols-2 gap-3">
        <label className="block text-right text-sm text-app-muted-light">
          الجنس
          <Dropdown
            className="mt-2 text-white"
            buttonClassName="bg-app-card-soft h-11"
            value={form.gender}
            onChange={(val) => updateField("gender", val)}
            options={[
              { value: "male", label: "ذكر" },
              { value: "female", label: "أنثى" },
            ]}
          />
        </label>

        <label className="block text-right text-sm text-app-muted-light">
          تاريخ الميلاد
          <input
            value={form.dob}
            onChange={(event) => updateField("dob", event.target.value)}
            className="app-input mt-2 h-11 w-full px-3 text-right outline-none focus:border-app-yellow/70 bg-app-card-soft text-white"
            type="date"
          />
        </label>
      </div>

      <div className="grid grid-cols-2 gap-3">
        <label className="block text-right text-sm text-app-muted-light">
          رقم الهاتف
          <input
            value={form.phone}
            onChange={(event) => updateField("phone", event.target.value)}
            className="app-input mt-2 h-11 w-full px-3 text-left outline-none focus:border-app-yellow/70 bg-app-card-soft text-white"
            placeholder="09xx xxx xxx"
            dir="ltr"
          />
        </label>

        <label className="block text-right text-sm text-app-muted-light">
          البريد الإلكتروني
          <input
            value={form.email}
            onChange={(event) => updateField("email", event.target.value)}
            className="app-input mt-2 h-11 w-full px-3 text-left outline-none focus:border-app-yellow/70 bg-app-card-soft text-white"
            placeholder="coach@example.com"
            dir="ltr"
            type="email"
          />
        </label>
      </div>

      <label className="block text-right text-sm text-app-muted-light">
        الفرع التابع له
        <Dropdown
          className="mt-2 text-white"
          buttonClassName="bg-app-card-soft h-11"
          value={form.branch_id}
          onChange={(val) => updateField("branch_id", val)}
          options={branches.map((b) => ({ value: String(b.id), label: b.name }))}
          placeholder="اختر الفرع"
        />
      </label>

      <label className="block text-right text-sm text-app-muted-light">
        التخصص التدريبي
        <input
          value={form.specialization}
          onChange={(event) => updateField("specialization", event.target.value)}
          className="app-input mt-2 h-11 w-full px-3 text-right outline-none focus:border-app-yellow/70 bg-app-card-soft text-white"
          placeholder="مثال: Yoga, CrossFit, Bodybuilding"
        />
      </label>

      <label className="block text-right text-sm text-app-muted-light">
        سنوات الخبرة
        <input
          value={form.experience_years}
          onChange={(event) => updateField("experience_years", event.target.value)}
          className="app-input mt-2 h-11 w-full px-3 text-right outline-none focus:border-app-yellow/70 bg-app-card-soft text-white"
          type="number"
          min="0"
        />
      </label>

      <label className="block text-right text-sm text-app-muted-light">
        نوع التوظيف
        <Dropdown
          className="mt-2 text-white"
          buttonClassName="bg-app-card-soft h-11"
          value={form.employment_type}
          onChange={(val) => updateField("employment_type", val)}
          options={employmentTypes}
        />
      </label>

      <label className="block text-right text-sm text-app-muted-light">
        الراتب الأساسي ({CURRENCY_SYMBOL})
        <input
          value={form.base_salary}
          onChange={(event) => updateField("base_salary", event.target.value)}
          className="app-input mt-2 h-11 w-full px-3 text-right outline-none focus:border-app-yellow/70 bg-app-card-soft text-white"
          type="number"
          min="0"
          required
        />
      </label>

      <label className="block text-right text-sm text-app-muted-light">
        العنوان السكني
        <input
          value={form.address}
          onChange={(event) => updateField("address", event.target.value)}
          className="app-input mt-2 h-11 w-full px-3 text-right outline-none focus:border-app-yellow/70 bg-app-card-soft text-white"
          placeholder="المدينة، الحي"
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
          إضافة المدرب
        </Button>
      </div>
    </form>
  );
}

function CoachEditForm({
  initialValues,
  onSubmit,
  onCancel,
  isLoading,
  errorMessage,
}) {
  const [form, setForm] = useState({
    base_salary: String(initialValues.basic?.base_salary || 0),
    employment_type: initialValues.basic?.employment_type || "fixed_salary",
    is_active: initialValues.basic?.is_active !== false,
    specialization: initialValues.details?.specialization || "",
    experience_years: String(initialValues.details?.experience_years || 0),
  });

  function updateField(field, value) {
    setForm((current) => ({ ...current, [field]: value }));
  }

  function handleSubmit(event) {
    event.preventDefault();
    onSubmit(
      {
        base_salary: Number(form.base_salary) || 0,
        employment_type: form.employment_type,
        is_active: form.is_active,
      },
      {
        specialization: form.specialization.trim() || null,
        experience_years: Number(form.experience_years) || 0,
      },
    );
  }

  return (
    <form onSubmit={handleSubmit} className="space-y-4" dir="rtl">
      <label className="block text-right text-sm text-app-muted-light">
        التخصص التدريبي
        <input
          value={form.specialization}
          onChange={(event) => updateField("specialization", event.target.value)}
          className="app-input mt-2 h-11 w-full px-3 text-right outline-none focus:border-app-yellow/70 bg-app-card-soft text-white"
          placeholder="مثال: Yoga, CrossFit"
        />
      </label>

      <label className="block text-right text-sm text-app-muted-light">
        سنوات الخبرة
        <input
          value={form.experience_years}
          onChange={(event) => updateField("experience_years", event.target.value)}
          className="app-input mt-2 h-11 w-full px-3 text-right outline-none focus:border-app-yellow/70 bg-app-card-soft text-white"
          type="number"
          min="0"
        />
      </label>

      <label className="block text-right text-sm text-app-muted-light">
        نوع التوظيف
        <Dropdown
          className="mt-2 text-white"
          buttonClassName="bg-app-card-soft h-11"
          value={form.employment_type}
          onChange={(val) => updateField("employment_type", val)}
          options={employmentTypes}
        />
      </label>

      <label className="block text-right text-sm text-app-muted-light">
        الراتب الأساسي ({CURRENCY_SYMBOL})
        <input
          value={form.base_salary}
          onChange={(event) => updateField("base_salary", event.target.value)}
          className="app-input mt-2 h-11 w-full px-3 text-right outline-none focus:border-app-yellow/70 bg-app-card-soft text-white"
          type="number"
          min="0"
          required
        />
      </label>

      <div className="flex items-center gap-3 pt-2">
        <label className="relative inline-flex cursor-pointer items-center">
          <input
            type="checkbox"
            checked={form.is_active}
            onChange={(e) => updateField("is_active", e.target.checked)}
            className="peer sr-only"
          />
          <div className="peer h-6 w-11 rounded-full bg-app-line after:absolute after:top-[2px] after:right-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all peer-checked:bg-app-yellow peer-checked:after:-translate-x-[18px]"></div>
        </label>
        <span className="text-sm font-medium text-white">مدرب نشط في النظام</span>
      </div>

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
          حفظ التعديل
        </Button>
      </div>
    </form>
  );
}

export default function CoachesClient() {
  const {
    search,
    setSearch,
    branchFilter,
    setBranchFilter,
    employmentFilter,
    setEmploymentFilter,
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
  } = useCoaches();

  const columns = useMemo(
    () => [
      {
        key: "name",
        label: "المدرب",
        align: "center",
        render: (_, coach) => (
          <span className="text-sm font-medium text-white">
            {coach.person?.full_name || "-"}
          </span>
        ),
      },
      {
        key: "specialization",
        label: "التخصص",
        align: "center",
        render: (_, coach) => (
          <span className="text-xs text-app-muted-light">
            {coach.details?.specialization || "غير محدد"}
          </span>
        ),
      },
      {
        key: "employment_type",
        label: "التوظيف",
        align: "center",
        render: (value) => (
          <span className="text-xs text-app-muted-light">
            {employmentLabels[value] || value}
          </span>
        ),
      },
      {
        key: "base_salary",
        label: "الراتب الأساسي",
        align: "center",
        render: (value) => (
          <span className="text-xs font-semibold text-app-green">
            {formatMoney(value)}
          </span>
        ),
      },
      {
        key: "is_active",
        label: "الحالة",
        align: "center",
        render: (value) => (
          <span
            className={`inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold ${
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
        render: (_, coach) => (
          <RowActions
            disabled={isDeleting}
            onEdit={() => {
              setFormError("");
              setSelectedCoachId(coach.id);
              setDrawerMode("edit");
            }}
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

  return (
    <div className="space-y-6" dir="rtl">
      <PageHeader
        eyebrow="إدارة النظام"
        title="إدارة المدربين"
        subtitle="عرض قائمة الكوادر التدريبية، إحصاءات الأجور، تعيين المهام والرياضات لكل كوتش."
        action={
          <Button
            icon={<PlusIcon className="size-4" />}
            onClick={() => {
              setFormError("");
              setDrawerMode("create");
            }}
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
              <Button
                tone="outline"
                className="h-9 px-3 text-xs"
                onClick={refetch}
              >
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
          <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
            <label className="relative block min-w-64">
              <SearchIcon className="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-app-muted-light" />
              <input
                className="app-input h-10 w-full pr-9 pl-3 text-sm outline-none transition focus:border-app-yellow/70 bg-app-card-soft text-white"
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
        open={drawerMode === "create"}
        onClose={closeDrawer}
        title="إضافة مدرب جديد"
        subtitle="أدخل بيانات الموظف والراتب الأساسي وقسم التخصص"
      >
        <CoachCreateForm
          branches={branches}
          onSubmit={handleCreate}
          onCancel={closeDrawer}
          isLoading={isCreating}
          errorMessage={formError}
        />
      </Drawer>

      <Drawer
        open={drawerMode === "edit"}
        onClose={closeDrawer}
        title="تعديل بيانات المدرب"
        subtitle={selectedCoach?.person?.full_name || ""}
      >
        {editInitialValues && (
          <CoachEditForm
            key={selectedCoachId || "edit"}
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
