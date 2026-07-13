"use client";

import Link from "next/link";
import { useMemo, useState } from "react";
import PageHeader from "@/components/common/PageHeader";
import Button from "@/components/ui/Button";
import Drawer from "@/components/ui/Drawer";
import SkeletonPage from "@/components/ui/Skeleton";
import { PlusIcon, FilterIcon, LockerIcon, SearchIcon, TrashIcon } from "@/components/icons/Icons";
import ConfirmDialog from "@/components/ui/ConfirmDialog";
import SearchInput from "@/components/ui/SearchInput";
import Dropdown from "@/components/ui/Dropdown";
import { Field } from "@/components/forms/FormControls";
import { useLockers } from "./useLockers";
import { useGetBranchesQuery } from "@/lib/api/branchesApi";
import { formatLocalizedName } from "@/lib/utils";
import { initialLockerForm, lockerSchema } from "@/lib/validations/lockersSchema";

const statusLabels = {
  available: "متاح",
  assigned: "محجوز",
  maintenance: "صيانة",
  disabled: "معطل",
};

const statusColors = {
  available: "bg-app-green/20 text-app-green border-app-green/30",
  assigned: "bg-app-blue/20 text-app-blue border-app-blue/30",
  maintenance: "bg-app-orange/20 text-app-orange border-app-orange/30",
  disabled: "bg-app-muted/20 text-app-muted-light border-app-muted/30",
};

export default function LockersClient() {
  const {
    search,
    setSearch,
    branchFilter,
    setBranchFilter,
    drawerOpen,
    setDrawerOpen,
    formError,
    lockers,
    isLoading,
    isFetching,
    handleCreate,
    isCreating,
    deleteConfirmOpen,
    itemToDelete,
    handleDeleteClick,
    closeDeleteConfirm,
    confirmDelete,
    isDeleting,
  } = useLockers();

  const { data: branchesData } = useGetBranchesQuery({});
  const branches = useMemo(() => branchesData?.data || [], [branchesData]);

  const branchOptions = useMemo(
    () => [
      { value: "all", label: "كل الفروع" },
      ...branches.map((b) => ({
        value: String(b.id),
        label: formatLocalizedName(b.name),
      })),
    ],
    [branches]
  );

  return (
    <div className="space-y-6">
      <PageHeader
        title="الخزائن"
        subtitle="إدارة الخزائن المتاحة وتتبع حالتها."
        icon={LockerIcon}
        action={
          <Link href="/management/lockers/create">
            <Button>
              <PlusIcon className="size-5 ml-2" />
              إضافة خزانة
            </Button>
          </Link>
        }
      />


      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between rounded-xl border border-app-line bg-app-card p-4">
        <div className="flex flex-1 items-center gap-4">
          <div className="w-full sm:max-w-xs">
            <SearchInput
              placeholder="البحث برقم الخزانة..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
            />
          </div>
        </div>
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
          <div className="flex items-center gap-2">
            <FilterIcon className="size-4 text-app-muted-light" />
            <Dropdown
              options={branchOptions}
              value={branchFilter}
              onChange={setBranchFilter}
              className="w-[180px]"
            />
          </div>
        </div>
      </div>

      {(isLoading || isFetching) && lockers.length === 0 ? (
        <SkeletonPage blocks={[{ type: "grid", cards: 8 }]} />
      ) : lockers.length === 0 ? (
        <div className="rounded-xl border border-app-line border-dashed bg-app-card-soft p-12 text-center">
          <div className="mx-auto flex size-12 items-center justify-center rounded-full bg-app-muted/30">
            <LockerIcon className="size-6 text-app-muted-light" />
          </div>
          <h3 className="mt-4 text-lg font-medium text-white">لا توجد خزائن</h3>
          <p className="mt-1 text-sm text-app-muted-light">
            لم يتم العثور على أي خزائن مطابقة للبحث.
          </p>
        </div>
      ) : (
        <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
          {lockers.map((locker) => (
            <div
              key={locker.id}
              className="group relative flex flex-col items-center justify-between overflow-hidden rounded-xl border border-app-line bg-app-card p-4 text-center transition-all hover:border-app-yellow/50 hover:shadow-lg"
            >
              <div className="mt-4 flex flex-col items-center">
                <div className="mb-4 flex size-14 items-center justify-center rounded-full bg-[#3a3a3a]">
                  <LockerIcon className="size-6 text-[#aaaaaa]" />
                </div>
                <div className="mb-2 text-2xl font-bold text-white tracking-wider">
                  {locker.locker_number}
                </div>
                <div className="text-xs text-app-muted-light">
                  {branches.find((b) => b.id === locker.branch_id)
                    ? formatLocalizedName(branches.find((b) => b.id === locker.branch_id).name)
                    : `فرع #${locker.branch_id}`}
                </div>
              </div>

              <div className="mt-6 flex w-full flex-col items-center gap-3">
                <span
                  className={`rounded-full border px-3 py-1 text-[11px] font-medium ${
                    statusColors[locker.status] || statusColors.disabled
                  }`}
                >
                  {statusLabels[locker.status] || locker.status}
                </span>

                {/* Hover Actions */}
                <div className="absolute inset-x-0 bottom-0 flex h-0 items-center justify-center gap-2 bg-app-card-hover/95 opacity-0 backdrop-blur-sm transition-all duration-300 group-hover:h-16 group-hover:opacity-100 border-t border-app-line">
                  <button
                    onClick={() => handleDeleteClick(locker)}
                    className="flex size-8 items-center justify-center rounded-full bg-app-muted/30 text-white transition-colors hover:bg-app-red hover:text-white"
                    title="حذف الخزانة"
                  >
                    <TrashIcon className="size-4" />
                  </button>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}

      <ConfirmDialog
        open={deleteConfirmOpen}
        onClose={closeDeleteConfirm}
        onConfirm={confirmDelete}
        title="حذف الخزانة"
        message={`هل أنت متأكد من رغبتك في حذف الخزانة "${itemToDelete?.locker_number}"؟ لا يمكن التراجع عن هذا الإجراء.`}
        isLoading={isDeleting}
      />
    </div>
  );
}

export function LockerCreateForm({
  formId,
  onSubmit,
  onCancel,
  isLoading,
  errorMessage,
  branches,
  showFooterActions = true,
}) {
  const [form, setForm] = useState(initialLockerForm);
  const [errors, setErrors] = useState({});

  function updateField(field, value) {
    setForm((current) => ({ ...current, [field]: value }));
    if (errors && errors[field])
      setErrors((current) => ({ ...current, [field]: null }));
  }

  function handleSubmit(event) {
    event.preventDefault();
    const data = {
      locker_number: form.locker_number.trim(),
      branch_id: Number(form.branch_id),
    };

    const result = lockerSchema.safeParse(data);
    if (!result.success) {
      const formattedErrors = {};
      result.error.issues.forEach((issue) => {
        formattedErrors[issue.path.join("_")] = issue.message;
      });
      setErrors(formattedErrors);
      return;
    }

    setErrors({});
    onSubmit(data);
  }

  const branchOptions = branches.map((b) => ({
    value: String(b.id),
    label: formatLocalizedName(b.name),
  }));

  return (
    <form id={formId} onSubmit={handleSubmit} className="flex flex-col gap-5">
      {errorMessage && (
        <div className="rounded-xl border border-app-red/30 bg-app-red/10 p-4 text-sm text-app-red">
          {errorMessage}
        </div>
      )}
      
      <div className="grid gap-5 md:grid-cols-2">
        <div className="flex flex-col gap-1.5 text-start">
          <label className="text-sm font-medium text-white flex items-center gap-1">
            الفرع <span className="text-app-red">*</span>
          </label>
          <Dropdown
            options={[{ value: "", label: "اختر الفرع..." }, ...branchOptions]}
            value={form.branch_id}
            onChange={(val) => updateField("branch_id", val)}
            error={errors.branch_id}
          />
          {errors.branch_id && (
            <span className="text-xs text-app-red">{errors.branch_id}</span>
          )}
        </div>

        <div className="flex flex-col gap-1.5 text-start">
          <label className="text-sm font-medium text-white flex items-center gap-1">
            رقم الخزانة <span className="text-app-red">*</span>
          </label>
          <div className="relative w-full">
            <div className="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
              <LockerIcon className="size-4 text-app-muted-light" />
            </div>
            <input
              type="text"
              className={`form-input pl-3 pr-10 ${errors.locker_number ? "border-app-red/50 focus:border-app-red focus:ring-app-red/20" : ""}`}
              value={form.locker_number}
              onChange={(e) => updateField("locker_number", e.target.value)}
              placeholder="L-001"
              dir="ltr"
            />
          </div>
          {errors.locker_number && (
            <span className="text-xs text-app-red">{errors.locker_number}</span>
          )}
        </div>
      </div>

      {showFooterActions && (
        <div className="mt-4 flex items-center justify-end gap-3 border-t border-app-line pt-4">
          <Button type="button" variant="ghost" onClick={onCancel} disabled={isLoading}>
            إلغاء
          </Button>
          <Button type="submit" isLoading={isLoading}>
            حفظ
          </Button>
        </div>
      )}
    </form>
  );
}
