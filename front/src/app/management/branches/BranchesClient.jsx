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
import { PlusIcon, FilterIcon } from "@/components/icons/Icons";
import ConfirmDialog from "@/components/ui/ConfirmDialog";
import DetailItem from "@/components/ui/DetailItem";
import SearchInput from "@/components/ui/SearchInput";
import ToggleSwitch from "@/components/ui/ToggleSwitch";
import { Field, PhoneField } from "@/components/forms/FormControls";
import { useBranches } from "./useBranches";
import { formatDate, formatLocalizedName } from "@/lib/utils";
import { genderLabels } from "@/lib/constants";
import { branchSchema } from "@/lib/validations/branchesSchema";

const TABLE_GRID_COLUMNS = "minmax(180px,1.2fr) 120px 180px 140px 110px 110px";

export const branchInitialForm = {
  club_id: "",
  name_ar: "",
  name_en: "",
  gender_restriction: "mixed",
  address: "",
  country_code: "+963",
  phone: "",
  type: "gym",
};

const initialForm = branchInitialForm;

const genderOptions = [
  { value: "all", label: "كل الفروع" },
  { value: "mixed", label: "مختلط" },
  { value: "male", label: "ذكور" },
  { value: "female", label: "إناث" },
];

const branchName = (branch) => formatLocalizedName(branch?.name);


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

export function BranchForm({
  mode,
  initialValues = initialForm,
  clubs = [],
  onSubmit,
  onCancel,
  isLoading,
  errorMessage,
  formId,
  showFooterActions = true,
  formClassName = "flex flex-col min-h-full",
}) {
  const [form, setForm] = useState(initialValues);
  const [errors, setErrors] = useState({});

  function updateField(field, value) {
    setForm((current) => ({ ...current, [field]: value }));
    if (errors[field]) setErrors((current) => ({ ...current, [field]: null }));
  }

  function handleSubmit(event) {
    event.preventDefault();
    const validationData = {
      club_id: Number(form.club_id),
      name_ar: form.name_ar.trim(),
      name_en: form.name_en.trim(),
      gender_restriction: form.gender_restriction,
      type: "gym",
      address: form.address.trim(),
      country_code: form.country_code.trim(),
      phone: form.phone.trim(),
    };
    
    const result = branchSchema.safeParse(validationData);
    if (!result.success) {
      const formattedErrors = {};
      result.error.issues.forEach(issue => {
        formattedErrors[issue.path.join('_')] = issue.message;
      });
      setErrors(formattedErrors);
      return;
    }
    
    setErrors({});
    onSubmit({
      club_id: validationData.club_id,
      name: validationData.name_ar,
      gender_restriction: validationData.gender_restriction,
      type: validationData.type,
      address: validationData.address || null,
      country_code: validationData.country_code || null,
      phone: validationData.phone || null,
    });
  }

  return (
    <form id={formId} noValidate onSubmit={handleSubmit} className={formClassName} dir="rtl">
      <div className="space-y-4 flex-1 pb-4">
      <label className="block text-right text-sm text-app-muted-light">
        النادي التابع له
        <Dropdown
          className="mt-2 text-white"
          buttonClassName="bg-app-card-soft h-11"
          value={form.club_id}
          onChange={(val) => updateField("club_id", val)}
          options={clubs.map((club) => ({ value: String(club.id), label: club.name }))}
          placeholder="اختر النادي"
         error={errors.club_id}
          />
      </label>

      <Field
        label="اسم الفرع بالعربية"
        value={form.name_ar}
        onChange={(event) => updateField("name_ar", event.target.value)}
        placeholder="مثال: الفرع الرئيسي"
        required
        type="text"
       error={errors.name_ar}
        />

      <Field
        label="اسم الفرع بالإنجليزية"
        value={form.name_en}
        onChange={(event) => updateField("name_en", event.target.value)}
        placeholder="Main Branch"
        dir="ltr"
        required
        type="text"
       error={errors.name_en}
        />

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
         error={errors.gender_restriction}
          />
      </label>

      <Field
        label="العنوان"
        value={form.address}
        onChange={(event) => updateField("address", event.target.value)}
        placeholder="مثال: حلب، الشهباء"
        required={false}
        type="text"
       error={errors.address}
        />

      <PhoneField
        label="رقم الهاتف"
        phoneValue={form.phone}
        onPhoneChange={(val) => updateField("phone", val)}
        codeValue={form.country_code}
        onCodeChange={(val) => updateField("country_code", val)}
        required={false}
       error={errors.phone || errors.country_code}
        />

      </div>

      {errorMessage && (
        <p className="rounded-xl border border-app-red/30 bg-app-red/10 p-3 text-center text-xs text-app-red mb-4">
          {errorMessage}
        </p>
      )}

      <div className={`${showFooterActions ? "sticky flex" : "entry-form-actions-hidden"} bottom-[-20px] -mx-5 -mb-5 mt-4 items-center gap-3 border-t border-app-line bg-app-bg p-5 z-10`}>
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
    isUpdating,
    isDeleting,
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
            <ToggleSwitch
              checked={value}
              onChange={() => handleToggleStatus(branch)}
              size="sm"
            />
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
            editHref={`/management/branches/create?mode=edit&id=${branch.id}`}
            onDelete={() => handleDelete(branch)}
          />
        ),
      },
    ],
    [isDeleting, handleDelete, handleToggleStatus],
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
            href="/management/branches/create"
            icon={<PlusIcon className="size-4" style={{ color: "#000000" }} />}
            style={{ color: "#000000" }}
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
            <SearchInput
              value={search}
              onChange={(event) => setSearch(event.target.value)}
              placeholder="البحث باسم الفرع أو العنوان..."
            />

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
