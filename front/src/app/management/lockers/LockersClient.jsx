"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { useMemo, useState } from "react";
import PageHeader from "@/components/common/PageHeader";
import Button from "@/components/ui/Button";
// import Drawer from "@/components/ui/Drawer";
import SkeletonPage from "@/components/ui/Skeleton";
import {
  PlusIcon,
  FilterIcon,
  LockerIcon,
  SearchIcon,
  TrashIcon,
  PencilIcon,
  CalendarIcon,
  XIcon,
} from "@/components/icons/Icons";
import ConfirmDialog from "@/components/ui/ConfirmDialog";
import SearchInput from "@/components/ui/SearchInput";
import Dropdown from "@/components/ui/Dropdown";
import Drawer from "@/components/ui/Drawer";
import { Field } from "@/components/forms/FormControls";
import {
  useGetLockersQuery,
  useCreateLockerMutation,
  useDeleteLockerMutation,
  useToggleLockerStatusMutation,
  useUpdateLockerMutation,
  useReserveLockerMutation,
  useReleaseLockerReservationMutation,
} from "@/lib/api/lockersApi";
import { useGetMembersQuery } from "@/lib/api/membersApi";
import { useLockers } from "./useLockers";
import { useGetBranchesQuery } from "@/lib/api/branchesApi";
import { formatLocalizedName, getBranchesArray } from "@/lib/utils";
import {
  initialLockerForm,
  lockerSchema,
  initialUpdateLockerForm,
  updateLockerSchema,
  initialReserveLockerForm,
  reserveLockerSchema,
} from "@/lib/validations/lockersSchema";

const statusLabels = {
  available: "متاح",
  assigned: "محجوز",
  with_member: "محجوز",
  with_staff: "محجوز",
  maintenance: "صيانة",
  disabled: "معطل",
};

const statusColors = {
  available: "bg-app-green/20 text-app-green border-app-green/30",
  assigned: "bg-app-blue/20 text-app-blue border-app-blue/30",
  with_member: "bg-app-blue/20 text-app-blue border-app-blue/30",
  with_staff: "bg-app-blue/20 text-app-blue border-app-blue/30",
  maintenance: "bg-app-orange/20 text-app-orange border-app-orange/30",
  disabled: "bg-app-muted/20 text-app-muted-light border-app-muted/30",
};

export default function LockersClient() {
  const router = useRouter();
  const { data: membersData } = useGetMembersQuery();
  const {
    search,
    setSearch,
    branchFilter,
    setBranchFilter,
    statusFilter,
    setStatusFilter,
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
    
    updateModalOpen,
    itemToUpdate,
    handleUpdateClick,
    closeUpdateModal,
    handleUpdate,
    isUpdating,

    reserveModalOpen,
    itemToReserve,
    handleReserveClick,
    closeReserveModal,
    handleReserve,
    isReserving,

    releaseConfirmOpen,
    itemToRelease,
    handleReleaseClick,
    closeReleaseConfirm,
    confirmRelease,
    isReleasing,
  } = useLockers();

  const { data: branchesData } = useGetBranchesQuery({});
  const branches = useMemo(() => getBranchesArray(branchesData), [branchesData]);

  const branchOptions = useMemo(
    () => [
      { value: "all", label: "كل الفروع" },
      ...branches.map((b) => ({
        value: String(b.id),
        label: formatLocalizedName(b.name),
      })),
    ],
    [branches],
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
          <div className="grid grid-cols-1 items-center gap-2 sm:grid-cols-2">
            <FilterIcon className="size-4 text-app-muted-light" />
            <Dropdown
              options={branchOptions}
              value={branchFilter}
              onChange={setBranchFilter}
              className="w-full sm:w-[180px]"
            />
            <Dropdown
              options={[
                { value: "all", label: "كل الحالات" },
                { value: "available", label: "متاحة" },
                { value: "occupied", label: "مشغولة" },
              ]}
              value={statusFilter}
              onChange={setStatusFilter}
              className="w-full sm:w-[180px]"
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
                    ? formatLocalizedName(
                        branches.find((b) => b.id === locker.branch_id).name,
                      )
                    : `فرع #${locker.branch_id}`}
                </div>
              </div>

              <div className="mt-6 flex w-full flex-col items-center gap-3">
                  <span
                    className={`rounded-full border px-3 py-1 text-[11px] font-medium ${
                      statusColors[locker.status] || statusColors.disabled
                    }`}
                  >
                    {(() => {
                      if (["assigned", "with_member", "with_staff"].includes(locker.status) || locker.holder_id) {
                        if (locker.holder_type === "member" && locker.holder_id) {
                          const m = membersData?.data?.find((m) => String(m.id) === String(locker.holder_id));
                          if (m) {
                            const person = m.person || {};
                            return (person.full_name || `${m.first_name || ""} ${m.last_name || ""}`).trim() || `لاعب #${m.id}`;
                          }
                          return `لاعب #${locker.holder_id}`;
                        } else if (locker.holder_type === "staff" && locker.holder_id) {
                          return `موظف #${locker.holder_id}`;
                        } else if (locker.holder_name) {
                          return locker.holder_name;
                        }
                      }
                      return statusLabels[locker.status] || locker.status;
                    })()}
                  </span>

                {/* Hover Actions */}
                <div className="absolute inset-x-0 bottom-0 flex h-0 items-center justify-center gap-2 bg-app-card-hover/95 opacity-0 backdrop-blur-sm transition-all duration-300 group-hover:h-16 group-hover:opacity-100 border-t border-app-line">
                  <Link
                    href={`/management/lockers/${locker.id}/edit`}
                    className="flex size-8 items-center justify-center rounded-full bg-app-muted/30 text-white transition-colors hover:bg-app-blue hover:text-white"
                    title="تعديل الخزانة"
                  >
                    <PencilIcon className="size-4" />
                  </Link>
                  
                  {locker.status === "available" && (
                    <button
                      onClick={() => handleReserveClick(locker)}
                      className="flex size-8 items-center justify-center rounded-full bg-app-muted/30 text-white transition-colors hover:bg-app-green hover:text-white"
                      title="حجز الخزانة"
                    >
                      <CalendarIcon className="size-4" />
                    </button>
                  )}

                  {locker.status === "assigned" && (
                    <button
                      onClick={() => handleReleaseClick(locker)}
                      className="flex size-8 items-center justify-center rounded-full bg-app-muted/30 text-white transition-colors hover:bg-app-orange hover:text-white"
                      title="فك الحجز"
                    >
                      <XIcon className="size-4" />
                    </button>
                  )}
                  
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

      <ConfirmDialog
        open={releaseConfirmOpen}
        onClose={closeReleaseConfirm}
        onConfirm={confirmRelease}
        title="فك الحجز"
        message={`هل أنت متأكد من رغبتك في فك حجز الخزانة "${itemToRelease?.locker_number}"؟`}
        isLoading={isReleasing}
      />

      <Drawer
        open={reserveModalOpen}
        onClose={closeReserveModal}
        title="حجز خزانة"
        subtitle={`حجز الخزانة ${itemToReserve?.locker_number}`}
      >
        {itemToReserve && (
          <LockerReserveForm
            formId="reserve-locker-form"
            onSubmit={handleReserve}
            onCancel={closeReserveModal}
            isLoading={isReserving}
          />
        )}
      </Drawer>
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
    <form id={formId} noValidate onSubmit={handleSubmit} className="flex flex-col gap-5">
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
        </div>

        <Field
          label="رقم الخزانة"
          type="text"
          required
          icon={LockerIcon}
          placeholder="L-001"
          value={form.locker_number}
          onChange={(e) => updateField("locker_number", e.target.value)}
          error={errors.locker_number}
          dir="ltr"
        />
      </div>

      {showFooterActions && (
        <div className="mt-4 flex items-center justify-end gap-3 border-t border-app-line pt-4">
          <Button
            type="button"
            tone="ghost"
            onClick={onCancel}
            disabled={isLoading}
          >
            إلغاء
          </Button>
          <Button type="submit" loading={isLoading}>
            حفظ
          </Button>
        </div>
      )}
    </form>
  );
}

export function LockerUpdateForm({
  formId,
  initialData,
  onSubmit,
  onCancel,
  isLoading,
  errorMessage,
}) {
  const [form, setForm] = useState({
    locker_number: initialData?.locker_number || "",
    status: initialData?.status || "available",
    holder_type: initialData?.holder_type || "",
    holder_id: initialData?.holder_id || "",
    holder_name: initialData?.holder_name || "",
  });
  const [errors, setErrors] = useState({});

  const { data: membersData } = useGetMembersQuery({}, { skip: form.holder_type !== "member" });
  const membersOptions = (membersData?.data || []).map(m => {
    const person = m.person || {};
    const fullName = (person.full_name || `${m.first_name || ""} ${m.last_name || ""}`).trim() || `عضو #${m.id}`;
    return {
      value: m.id,
      label: fullName
    };
  });

  function updateField(field, value) {
    setForm((current) => ({ ...current, [field]: value }));
    if (errors && errors[field])
      setErrors((current) => ({ ...current, [field]: null }));
  }

  function handleSubmit(event) {
    event.preventDefault();
    const data = {
      locker_number: form.locker_number.trim(),
      status: form.status,
    };
    if (form.holder_type) data.holder_type = form.holder_type;
    if (form.holder_id) data.holder_id = Number(form.holder_id);
    if (form.holder_name) data.holder_name = form.holder_name.trim();

    const result = updateLockerSchema.safeParse(data);
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

  return (
    <form id={formId} noValidate onSubmit={handleSubmit} className="flex flex-col gap-5">
      {errorMessage && (
        <div className="rounded-xl border border-app-red/30 bg-app-red/10 p-4 text-sm text-app-red">
          {errorMessage}
        </div>
      )}

      <div className="flex flex-col gap-4">
        <Field
          label="رقم الخزانة"
          type="text"
          required
          value={form.locker_number}
          onChange={(e) => updateField("locker_number", e.target.value)}
          error={errors.locker_number}
          dir="ltr"
        />

        <div className="flex flex-col gap-1.5 text-start">
          <label className="text-sm font-medium text-white flex items-center gap-1">
            الحالة <span className="text-app-red">*</span>
          </label>
          <Dropdown
            options={[
              { value: "available", label: "متاح" },
              { value: "assigned", label: "محجوز" },
              { value: "with_member", label: "مع لاعب" },
              { value: "with_staff", label: "مع موظف" },
              { value: "maintenance", label: "صيانة" },
              { value: "disabled", label: "معطل" },
            ]}
            value={form.status}
            onChange={(val) => updateField("status", val)}
            error={errors.status}
          />
        </div>

        <div className="flex flex-col gap-1.5 text-start">
          <label className="text-sm font-medium text-white">نوع المستفيد (اختياري)</label>
          <Dropdown
            options={[
              { value: "", label: "لا يوجد" },
              { value: "member", label: "لاعب" },
              { value: "staff", label: "موظف" },
              { value: "guest", label: "زائر" },
            ]}
            value={form.holder_type}
            onChange={(val) => updateField("holder_type", val)}
          />
        </div>

        <div className="flex flex-col gap-1.5 text-start">
          <label className="text-sm font-medium text-white flex items-center gap-1">
            المستفيد (اختياري)
          </label>
          {form.holder_type === "member" ? (
            <Dropdown
              searchable
              options={membersOptions}
              value={form.holder_id}
              onChange={(val) => updateField("holder_id", val)}
              placeholder="ابحث عن لاعب..."
            />
          ) : form.holder_type === "staff" ? (
            <Dropdown
              searchable
              options={[]}
              value={form.holder_id}
              onChange={(val) => updateField("holder_id", val)}
              placeholder="ابحث عن موظف... (قريباً)"
            />
          ) : (
            <Field
              type="number"
              value={form.holder_id}
              onChange={(e) => updateField("holder_id", e.target.value)}
              placeholder="أدخل رقم المستفيد"
            />
          )}
        </div>

        <Field
          label="اسم المستفيد (اختياري)"
          type="text"
          required={false}
          value={form.holder_name}
          onChange={(e) => updateField("holder_name", e.target.value)}
        />
      </div>

      <div className="mt-4 flex items-center justify-end gap-3 border-t border-app-line pt-4">
        <Button type="button" tone="ghost" onClick={onCancel} disabled={isLoading}>
          إلغاء
        </Button>
        <Button type="submit" loading={isLoading}>
          حفظ التعديلات
        </Button>
      </div>
    </form>
  );
}

export function LockerReserveForm({
  formId,
  onSubmit,
  onCancel,
  isLoading,
  errorMessage,
}) {
  const [form, setForm] = useState({
    reservation_type: "rental",
    holder_type: "member",
    holder_id: "",
    price: "",
    start_date: "",
    end_date: "",
  });
  const [errors, setErrors] = useState({});

  const { data: membersData } = useGetMembersQuery({}, { skip: form.holder_type !== "member" });
  const membersOptions = (membersData?.data || []).map(m => {
    const person = m.person || {};
    const fullName = (person.full_name || `${m.first_name || ""} ${m.last_name || ""}`).trim() || `عضو #${m.id}`;
    return {
      value: m.id,
      label: fullName
    };
  });

  function updateField(field, value) {
    setForm((current) => ({ ...current, [field]: value }));
    if (errors && errors[field])
      setErrors((current) => ({ ...current, [field]: null }));
  }

  function handleSubmit(event) {
    event.preventDefault();
    const data = {
      reservation_type: form.reservation_type,
      holder_type: form.holder_type,
      holder_id: Number(form.holder_id),
      start_date: form.start_date,
    };
    if (form.price) data.price = Number(form.price);
    if (form.end_date) data.end_date = form.end_date;

    const result = reserveLockerSchema.safeParse(data);
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

  return (
    <form id={formId} noValidate onSubmit={handleSubmit} className="flex flex-col gap-5">
      {errorMessage && (
        <div className="rounded-xl border border-app-red/30 bg-app-red/10 p-4 text-sm text-app-red">
          {errorMessage}
        </div>
      )}

      <div className="flex flex-col gap-4">
        <div className="flex flex-col gap-1.5 text-start">
          <label className="text-sm font-medium text-white flex items-center gap-1">
            نوع الحجز <span className="text-app-red">*</span>
          </label>
          <Dropdown
            options={[
              { value: "rental", label: "إيجار" },
              { value: "assign", label: "إسناد مجاني" },
            ]}
            value={form.reservation_type}
            onChange={(val) => updateField("reservation_type", val)}
            error={errors.reservation_type}
          />
        </div>

        <div className="flex flex-col gap-1.5 text-start">
          <label className="text-sm font-medium text-white flex items-center gap-1">
            نوع المستفيد <span className="text-app-red">*</span>
          </label>
          <Dropdown
            options={[
              { value: "member", label: "لاعب" },
              { value: "staff", label: "موظف" },
              { value: "guest", label: "زائر" },
            ]}
            value={form.holder_type}
            onChange={(val) => updateField("holder_type", val)}
            error={errors.holder_type}
          />
        </div>

        <div className="flex flex-col gap-1.5 text-start">
          <label className="text-sm font-medium text-white flex items-center gap-1">
            المستفيد <span className="text-app-red">*</span>
          </label>
          {form.holder_type === "member" ? (
            <Dropdown
              searchable
              options={membersOptions}
              value={form.holder_id}
              onChange={(val) => updateField("holder_id", val)}
              error={errors.holder_id}
              placeholder="ابحث عن لاعب..."
            />
          ) : form.holder_type === "staff" ? (
            <Dropdown
              searchable
              options={[]}
              value={form.holder_id}
              onChange={(val) => updateField("holder_id", val)}
              error={errors.holder_id}
              placeholder="ابحث عن موظف... (قريباً)"
            />
          ) : (
            <Field
              type="number"
              value={form.holder_id}
              onChange={(e) => updateField("holder_id", e.target.value)}
              error={errors.holder_id}
              placeholder="أدخل رقم المستفيد"
            />
          )}
        </div>

        {form.reservation_type === "rental" && (
          <>
            <Field
              label="السعر"
              type="number"
              required={false}
              value={form.price}
              onChange={(e) => updateField("price", e.target.value)}
              error={errors.price}
            />

            <Field
              label="تاريخ البداية"
              type="date"
              required
              value={form.start_date}
              onChange={(val) => updateField("start_date", val)}
              error={errors.start_date}
            />

            <Field
              label="تاريخ النهاية (اختياري)"
              type="date"
              required={false}
              value={form.end_date}
              onChange={(val) => updateField("end_date", val)}
              error={errors.end_date}
            />
          </>
        )}
      </div>

      <div className="mt-4 flex items-center justify-end gap-3 border-t border-app-line pt-4">
        <Button type="button" tone="ghost" onClick={onCancel} disabled={isLoading}>
          إلغاء
        </Button>
        <Button type="submit" loading={isLoading}>
          تأكيد الحجز
        </Button>
      </div>
    </form>
  );
}
