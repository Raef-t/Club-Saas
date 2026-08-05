"use client";

import { useState } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import Button from "@/components/ui/Button";
import ManagementCreatePage from "@/components/forms/ManagementCreatePage";
import { FormCard, UploadBox } from "@/components/forms/FormControls";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
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

  if (createdCredentials) {
    return (
      <ManagementCreatePage
        title="تمت إضافة الموظف"
        subtitle="إدارة الموظفين > بيانات الدخول"
        backHref="/management/staff"
      >
        <div className="mx-auto max-w-2xl">
          <FormCard title="بيانات الدخول الجديدة" className="entry-form-card p-5">
            <div className="space-y-4 text-right">
              <p className="rounded-xl border border-app-green/30 bg-app-green/10 p-4 text-sm leading-6 text-app-green">
                تمت إضافة الموظف بنجاح. احتفظ ببيانات الدخول التالية وسلّمها للموظف بطريقة آمنة.
              </p>
              <CredentialRow
                label="اسم المستخدم"
                value={createdCredentials.username}
                onCopy={copyValue}
                copied={copiedValue === createdCredentials.username}
              />
              <CredentialRow
                label="كلمة المرور"
                value={createdCredentials.password}
                onCopy={copyValue}
                copied={copiedValue === createdCredentials.password}
              />
              <Button href="/management/staff" className="w-full text-black">
                العودة إلى قائمة الموظفين
              </Button>
            </div>
          </FormCard>
        </div>
      </ManagementCreatePage>
    );
  }

  return (
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
  );
}
