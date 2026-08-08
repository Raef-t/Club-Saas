"use client";

import { useEffect, useState } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import Button from "@/components/ui/Button";
import ManagementCreatePage from "@/components/forms/ManagementCreatePage";
import { FormCard, UploadBox } from "@/components/forms/FormControls";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { XIcon } from "@/components/icons/Icons";
import StaffForm from "../StaffForm";
import { getStaffRecord } from "../staffUtils";
import { useStaff } from "../useStaff";

const FORM_ID = "staff-form";

function CredentialRow({ label, value, onCopy, copied }) {
  return (
    <div className="rounded-xl border border-app-line bg-app-card-soft p-4">
      <p className="text-xs text-app-muted-light">{label}</p>
      <div className="mt-2 flex items-center justify-between gap-3" dir="ltr">
        <code className="min-w-0 flex-1 truncate text-sm font-semibold text-app-yellow">
          {value}
        </code>
        <Button
          type="button"
          tone="outline"
          className="h-8 px-3 text-xs"
          onClick={() => onCopy(value)}
        >
          {copied ? "تم النسخ" : "نسخ"}
        </Button>
      </div>
    </div>
  );
}

function CredentialsDialog({ credentials, copiedValue, onCopy, onClose }) {
  useEffect(() => {
    if (!credentials) return;

    const previousOverflow = document.body.style.overflow;
    document.body.style.overflow = "hidden";

    function handleKeyDown(event) {
      if (event.key === "Escape") onClose();
    }

    document.addEventListener("keydown", handleKeyDown);
    return () => {
      document.body.style.overflow = previousOverflow;
      document.removeEventListener("keydown", handleKeyDown);
    };
  }, [credentials, onClose]);

  if (!credentials) return null;

  return (
    <div
      className="fixed inset-0 z-[90] flex items-center justify-center p-4"
      role="dialog"
      aria-modal="true"
      aria-labelledby="staff-credentials-title"
      dir="rtl"
    >
      <button
        type="button"
        className="absolute inset-0 cursor-default bg-[var(--app-overlay)] backdrop-blur-md"
        aria-label="إغلاق"
        onClick={onClose}
      />

      <div className="card-shell relative z-10 w-full max-w-lg rounded-2xl border border-app-line bg-app-panel p-6 shadow-2xl">
        <button
          type="button"
          onClick={onClose}
          className="absolute end-4 top-4 grid size-9 place-items-center rounded-lg border border-app-line bg-app-card-soft text-app-muted-light transition hover:border-app-yellow/60 hover:text-app-yellow"
          aria-label="إغلاق"
        >
          <XIcon className="size-4" />
        </button>

        <div className="space-y-4 pt-2 text-right">
          <div className="text-center">
            <h2 id="staff-credentials-title" className="text-xl font-semibold text-app-text">
              بيانات حساب الموظف
            </h2>
            <p className="mt-2 text-sm leading-6 text-app-green">
              تمت إضافة الموظف بنجاح. احتفظ ببيانات الدخول وسلّمها للموظف بطريقة آمنة.
            </p>
          </div>

          <CredentialRow
            label="اسم المستخدم"
            value={credentials.username}
            onCopy={onCopy}
            copied={copiedValue === credentials.username}
          />
          <CredentialRow
            label="كلمة المرور"
            value={credentials.password}
            onCopy={onCopy}
            copied={copiedValue === credentials.password}
          />

          <Button type="button" className="h-11 w-full text-black" onClick={onClose}>
            العودة إلى قائمة الموظفين
          </Button>
        </div>
      </div>
    </div>
  );
}

export default function StaffCreateClient() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const { selectedBranchId } = useManagementBranch();
  const editId = Number(searchParams.get("id"));
  const isEdit = searchParams.get("mode") === "edit" && Number.isFinite(editId) && editId > 0;
  const [photo, setPhoto] = useState([]);
  const [createdCredentials, setCreatedCredentials] = useState(null);
  const [copiedValue, setCopiedValue] = useState("");
  const {
    branches,
    formError,
    isCreating,
    isUpdating,
    detailsError,
    refetchDetails,
    handleCreate,
    handleUpdate,
    getEditInitialValues,
  } = useStaff({
    selectedStaffId: isEdit ? editId : null,
    fetchDetails: isEdit,
  });
  const editInitialValues = isEdit ? getEditInitialValues() : null;

  async function submit(values) {
    const response = await handleCreate({ ...values, photo: photo[0] || null });
    if (!response) return;

    const created = getStaffRecord(response) || {};
    const username = created.generated_username || response?.data?.generated_username || "";
    const password = created.generated_password || response?.data?.generated_password || "";

    if (username || password) {
      setCreatedCredentials({
        id: created.id,
        username: username || created.username || "-",
        password: password || "-",
      });
      return;
    }

    router.push("/management/staff");
  }

  async function submitEdit(values) {
    const response = await handleUpdate(values);
    if (response) router.push("/management/staff");
  }

  async function copyValue(value) {
    try {
      await navigator.clipboard.writeText(value);
      setCopiedValue(value);
      window.setTimeout(() => setCopiedValue(""), 1800);
    } catch {
      setCopiedValue("");
    }
  }

  function closeCredentialsDialog() {
    setCreatedCredentials(null);
    router.push("/management/staff");
  }

  return (
    <>
      <ManagementCreatePage
        title={isEdit ? "تعديل موظف" : "إضافة موظف"}
        subtitle={isEdit ? "إدارة الموظفين > تعديل موظف" : "إدارة الموظفين > إضافة موظف"}
        formId={FORM_ID}
        backHref="/management/staff"
        isSubmitting={isEdit ? isUpdating : isCreating}
        submitLabel={isEdit ? "حفظ التعديلات" : "إضافة الموظف"}
      >
        <div className={isEdit ? "mx-auto max-w-3xl" : "entry-form-side-layout"}>
          <FormCard title="البيانات الشخصية والوظيفية" className="entry-form-card p-5">
            {isEdit && detailsError ? (
              <div className="space-y-4 py-8 text-center">
                <p className="text-sm text-app-red">تعذر تحميل بيانات الموظف.</p>
                <Button tone="outline" onClick={refetchDetails}>
                  إعادة المحاولة
                </Button>
              </div>
            ) : isEdit && !editInitialValues ? (
              <p className="py-8 text-center text-sm text-app-muted-light">
                جاري تحميل بيانات الموظف...
              </p>
            ) : (
              <StaffForm
                key={isEdit ? `staff-edit-${editId}` : `staff-create-${selectedBranchId}`}
                formId={FORM_ID}
                branches={branches}
                initialValues={isEdit ? editInitialValues : null}
                onSubmit={isEdit ? submitEdit : submit}
                onCancel={() => router.push("/management/staff")}
                isLoading={isEdit ? isUpdating : isCreating}
                errorMessage={formError}
              />
            )}
          </FormCard>

          {!isEdit && (
            <UploadBox
              className="entry-form-card"
              label="صورة الموظف"
              subtitle="الصورة الشخصية (اختيارية)"
              accept=".png,.jpg,.jpeg"
              multiple={false}
              maxSizeMB={2}
              value={photo}
              onChange={setPhoto}
            />
          )}
        </div>
      </ManagementCreatePage>

      <CredentialsDialog
        credentials={createdCredentials}
        copiedValue={copiedValue}
        onCopy={copyValue}
        onClose={closeCredentialsDialog}
      />
    </>
  );
}
