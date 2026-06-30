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
import { PlusIcon, SearchIcon, FilterIcon } from "@/components/icons/Icons";
import ConfirmDialog from "@/components/ui/ConfirmDialog";
import { useBranches } from "./useBranches";

const TABLE_GRID_COLUMNS = "minmax(180px,1.2fr) 120px 180px 140px 110px 110px";

const initialForm = {
  club_id: "",
  name_ar: "",
  name_en: "",
  gender_restriction: "mixed",
  address: "",
  country_code: "+963",
  phone: "",
  type: "gym",
};

const genderLabels = {
  mixed: "مختلط",
  male: "ذكور",
  female: "إناث",
};

const genderOptions = [
  { value: "all", label: "كل الفروع" },
  { value: "mixed", label: "مختلط" },
  { value: "male", label: "ذكور" },
  { value: "female", label: "إناث" },
];

function branchName(branch) {
  if (!branch?.name) return "-";
  if (typeof branch.name === "string") return branch.name;
  return branch.name.ar || branch.name.en || "-";
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

function BranchDetails({ branch, isLoading, error }) {
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
        تعذر تحميل تفاصيل الفرع.
      </div>
    );
  }

  if (!branch) {
    return (
      <div className="rounded-xl border border-app-line bg-app-card-soft/60 p-6 text-center text-sm text-app-muted-light">
        لا توجد تفاصيل لهذا الفرع.
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="rounded-xl border border-app-line bg-app-card-soft/70 p-4 text-right">
        <h3 className="text-lg font-medium text-app-text">
          {branchName(branch)}
        </h3>
        <p className="mt-1 text-xs text-app-muted-light">
          رقم الفرع: #{branch.id}
        </p>
      </div>

      <section className="grid gap-3 sm:grid-cols-2">
        <DetailItem
          label="التقييد الجنسي"
          value={genderLabels[branch.gender_restriction] || branch.gender_restriction}
          tone="yellow"
        />
        <DetailItem
          label="حالة النشاط"
          value={branch.is_active ? "نشط" : "غير نشط"}
          tone={branch.is_active ? "green" : "red"}
        />
        <DetailItem
          label="رقم الهاتف"
          value={branch.phone ? `${branch.country_code || ""} ${branch.phone}` : "-"}
        />
        <DetailItem label="العنوان" value={branch.address} />
        <DetailItem label="تاريخ الإنشاء" value={formatDate(branch.created_at)} />
      </section>
    </div>
  );
}

function BranchForm({
  mode,
  initialValues = initialForm,
  clubs = [],
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
      club_id: Number(form.club_id),
      name: {
        ar: form.name_ar.trim(),
        en: form.name_en.trim(),
      },
      gender_restriction: form.gender_restriction,
      type: "gym",
      address: form.address.trim() || null,
      country_code: form.country_code.trim() || null,
      phone: form.phone.trim() || null,
    });
  }

  return (
    <form onSubmit={handleSubmit} className="space-y-4" dir="rtl">
      <label className="block text-right text-sm text-app-muted-light">
        النادي التابع له
        <Dropdown
          className="mt-2 text-white"
          buttonClassName="bg-app-card-soft h-11"
          value={form.club_id}
          onChange={(val) => updateField("club_id", val)}
          options={clubs.map((club) => ({ value: club.id, label: club.name }))}
          placeholder="اختر النادي"
        />
      </label>

      <label className="block text-right text-sm text-app-muted-light">
        اسم الفرع بالعربية
        <input
          value={form.name_ar}
          onChange={(event) => updateField("name_ar", event.target.value)}
          className="app-input mt-2 h-11 w-full px-3 text-right outline-none focus:border-app-yellow/70 bg-app-card-soft text-white"
          placeholder="مثال: الفرع الرئيسي"
          required
        />
      </label>

      <label className="block text-right text-sm text-app-muted-light">
        اسم الفرع بالإنجليزية
        <input
          value={form.name_en}
          onChange={(event) => updateField("name_en", event.target.value)}
          className="app-input mt-2 h-11 w-full px-3 text-left outline-none focus:border-app-yellow/70 bg-app-card-soft text-white"
          placeholder="Main Branch"
          dir="ltr"
          required
        />
      </label>

      <label className="block text-right text-sm text-app-muted-light">
        التقييد الجنسي
        <Dropdown
          className="mt-2 text-white"
          buttonClassName="bg-app-card-soft h-11"
          value={form.gender_restriction}
          onChange={(val) => updateField("gender_restriction", val)}
          options={[
            { value: "mixed", label: "مختلط" },
            { value: "male", label: "ذكور فقط" },
            { value: "female", label: "إناث فقط" },
          ]}
          placeholder="اختر التقييد الجنسي"
        />
      </label>

      <label className="block text-right text-sm text-app-muted-light">
        العنوان
        <input
          value={form.address}
          onChange={(event) => updateField("address", event.target.value)}
          className="app-input mt-2 h-11 w-full px-3 text-right outline-none focus:border-app-yellow/70 bg-app-card-soft text-white"
          placeholder="مثال: حلب، الشهباء"
        />
      </label>

      <div className="grid grid-cols-3 gap-3">
        <label className="col-span-2 block text-right text-sm text-app-muted-light">
          رقم الهاتف
          <input
            value={form.phone}
            onChange={(event) => updateField("phone", event.target.value)}
            className="app-input mt-2 h-11 w-full px-3 text-left outline-none focus:border-app-yellow/70 bg-app-card-soft text-white"
            placeholder="924325325"
            dir="ltr"
          />
        </label>
        <label className="block text-right text-sm text-app-muted-light">
          رمز الدولة
          <input
            value={form.country_code}
            onChange={(event) => updateField("country_code", event.target.value)}
            className="app-input mt-2 h-11 w-full px-3 text-center outline-none focus:border-app-yellow/70 bg-app-card-soft text-white"
            placeholder="+963"
            dir="ltr"
          />
        </label>
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
          {mode === "edit" ? "حفظ التعديل" : "إنشاء الفرع"}
        </Button>
      </div>
    </form>
  );
}

export default function BranchesClient() {
  const {
    search,
    setSearch,
    genderFilter,
    setGenderFilter,
    drawerMode,
    setDrawerMode,
    setSelectedBranchId,
    selectedBranchId,
    formError,
    setFormError,
    isLoading,
    error,
    refetch,
    filteredBranches,
    stats,
    selectedBranch,
    detailsBranch,
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
    handleToggleStatus,
    getEditInitialValues,
    clubs,
    closeDrawer,
  } = useBranches();

  const columns = useMemo(
    () => [
      {
        key: "name",
        label: "اسم الفرع",
        align: "center",
        render: (_, branch) => (
          <span className="text-sm font-medium text-app-text">
            {branchName(branch)}
          </span>
        ),
      },
      {
        key: "gender_restriction",
        label: "التقييد الجنسي",
        align: "center",
        render: (value) => (
          <span className="text-xs text-app-muted-light">
            {genderLabels[value] || value}
          </span>
        ),
      },
      {
        key: "address",
        label: "العنوان",
        align: "center",
        render: (value) => (
          <span className="text-xs text-app-muted-light truncate block max-w-44">
            {value || "-"}
          </span>
        ),
      },
      {
        key: "phone",
        label: "الهاتف",
        align: "center",
        render: (_, branch) => (
          <span className="text-xs text-app-muted-light" dir="ltr">
            {branch.phone ? `${branch.country_code || ""} ${branch.phone}` : "-"}
          </span>
        ),
      },
      {
        key: "is_active",
        label: "الحالة",
        align: "center",
        render: (value, branch) => (
          <div className="flex justify-center" onClick={(e) => e.stopPropagation()}>
            <label className="relative inline-flex cursor-pointer items-center">
              <input
                type="checkbox"
                checked={value}
                onChange={() => handleToggleStatus(branch)}
                className="peer sr-only"
              />
              <div className="peer h-5 w-9 rounded-full bg-app-line after:absolute after:top-[2px] after:right-[2px] after:h-4 after:w-4 after:rounded-full after:bg-white after:transition-all peer-checked:bg-app-yellow peer-checked:after:-translate-x-full"></div>
            </label>
          </div>
        ),
      },
      {
        key: "actions",
        label: "الإجراءات",
        align: "center",
        render: (_, branch) => (
          <RowActions
            disabled={isDeleting}
            onEdit={() => {
              setFormError("");
              setSelectedBranchId(branch.id);
              setDrawerMode("edit");
            }}
            onDelete={() => handleDelete(branch)}
          />
        ),
      },
    ],
    [isDeleting, handleDelete, setFormError, setSelectedBranchId, setDrawerMode, handleToggleStatus],
  );

  const editInitialValues = useMemo(() => {
    return getEditInitialValues();
  }, [selectedBranch, clubs]);

  return (
    <div className="space-y-6" dir="rtl">
      <PageHeader
        eyebrow="إدارة النظام"
        title="إدارة الفروع"
        subtitle="عرض فروع النادي المختلفة، تعديل بيانات الهاتف والعنوان، وتفعيل أو تعطيل الفروع."
        action={
          <Button
            icon={<PlusIcon className="size-4" />}
            onClick={() => {
              setFormError("");
              setDrawerMode("create");
            }}
          >
            إضافة فرع
          </Button>
        }
      />

      <StatsGrid items={stats} />

      <DataTable
        title="قائمة الفروع"
        columns={columns}
        rows={filteredBranches}
        minWidth="750px"
        tableColumns={TABLE_GRID_COLUMNS}
        showAdd={false}
        showSearch={false}
        showFilter={false}
        showExport={false}
        isLoading={isLoading}
        emptyMessage={
          error ? (
            <div className="space-y-3 text-center">
              <p className="text-app-red">تعذر تحميل الفروع.</p>
              <Button
                tone="outline"
                className="h-9 px-3 text-xs"
                onClick={refetch}
              >
                إعادة المحاولة
              </Button>
            </div>
          ) : (
            "لا توجد فروع مسجلة حالياً."
          )
        }
        rowClassName="gap-2 px-3 py-4"
        headerClassName="gap-2 px-3"
        totalPages={0}
        onRowClick={(branch) => {
          setSelectedBranchId(branch.id);
          setDrawerMode("details");
        }}
        getRowKey={(branch) => branch.id}
        toolbarActions={
          <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
            <label className="relative block min-w-64">
              <SearchIcon className="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-app-muted-light" />
              <input
                className="app-input h-10 w-full pr-9 pl-3 text-sm outline-none transition focus:border-app-yellow/70 bg-app-card-soft text-white"
                value={search}
                onChange={(event) => setSearch(event.target.value)}
                placeholder="البحث باسم الفرع أو العنوان..."
                type="search"
              />
            </label>

            <Dropdown
              className="min-w-48 bg-app-card-soft border-app-line text-white"
              icon={FilterIcon}
              value={genderFilter}
              options={genderOptions}
              onChange={setGenderFilter}
            />
          </div>
        }
        toolbarMeta={
          <p className="text-sm text-app-muted-light">
            النتائج:{" "}
            <span className="font-medium text-app-text">
              {filteredBranches.length.toLocaleString("ar")}
            </span>
          </p>
        }
      />

      <Drawer
        open={drawerMode === "create"}
        onClose={closeDrawer}
        title="إضافة فرع جديد"
        subtitle="أدخل بيانات الفرع والتقييد الجنسي ورقم الهاتف"
      >
        <BranchForm
          mode="create"
          clubs={clubs}
          onSubmit={handleCreate}
          onCancel={closeDrawer}
          isLoading={isCreating}
          errorMessage={formError}
          initialValues={{
            ...initialForm,
            club_id: clubs[0]?.id || "",
          }}
        />
      </Drawer>

      <Drawer
        open={drawerMode === "edit"}
        onClose={closeDrawer}
        title="تعديل بيانات الفرع"
        subtitle={selectedBranch ? branchName(selectedBranch) : ""}
      >
        {editInitialValues && (
          <BranchForm
            key={selectedBranchId || "edit"}
            mode="edit"
            clubs={clubs}
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
        title="تفاصيل الفرع"
        subtitle={detailsBranch ? branchName(detailsBranch) : (selectedBranch ? branchName(selectedBranch) : "")}
      >
        <BranchDetails
          branch={detailsBranch || selectedBranch}
          isLoading={isFetchingDetails}
          error={detailsError}
        />
      </Drawer>

      <ConfirmDialog
        open={deleteConfirmOpen}
        onClose={closeDeleteConfirm}
        onConfirm={confirmDelete}
        title="تأكيد حذف الفرع"
        message={`هل أنت متأكد من رغبتك في حذف فرع "${itemToDelete ? branchName(itemToDelete) : ""}"؟ لا يمكن التراجع عن هذا الإجراء.`}
        isLoading={isDeleting}
      />
    </div>
  );
}
