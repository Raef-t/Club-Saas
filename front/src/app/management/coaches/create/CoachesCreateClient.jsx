"use client";

import { useRouter, useSearchParams } from "next/navigation";
import ManagementCreatePage from "@/components/forms/ManagementCreatePage";
import { FormCard, UploadBox } from "@/components/forms/FormControls";
import { CoachCreateForm, CoachEditForm } from "../CoachesClient";
import { useCoaches } from "../useCoaches";

const FORM_ID = "create-coach-form";

export default function CoachesCreateClient() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const mode = searchParams.get("mode");
  const editId = searchParams.get("id");
  const isEdit = mode === "edit" && editId;
  const {
    branches,
    formError,
    isCreating,
    isUpdating,
    handleCreate,
    handleUpdate,
    getEditInitialValues,
  } = useCoaches({ selectedCoachId: isEdit ? Number(editId) : null });
  const firstBranchId = branches[0]?.id || "none";
  const editInitialValues = isEdit ? getEditInitialValues() : null;

  async function submit(values) {
    const ok = await handleCreate(values);
    if (ok) router.push("/management/coaches");
  }

  async function submitEdit(basicValues, detailsValues) {
    const ok = await handleUpdate(basicValues, detailsValues);
    if (ok) router.push("/management/coaches");
  }

  return (
    <ManagementCreatePage
      title={isEdit ? "تعديل مدرب" : "إضافة مدرب"}
      subtitle={isEdit ? "إدارة الأعضاء > تعديل مدرب" : "إدارة الأعضاء > إضافة مدرب"}
      formId={FORM_ID}
      backHref="/management/coaches"
      isSubmitting={isEdit ? isUpdating : isCreating}
      submitLabel={isEdit ? "حفظ التعديل" : "حفظ"}
    >
      <div className="entry-form-side-layout">
        <FormCard title="البيانات الشخصية" className="entry-form-card p-5">
          {isEdit ? (
            !editInitialValues ? (
              <p className="py-8 text-center text-sm text-app-muted-light">
                جاري تحميل بيانات المدرب...
              </p>
            ) : (
              <CoachEditForm
                key={`coach-edit-${editId}`}
                formId={FORM_ID}
                initialValues={editInitialValues}
                onSubmit={submitEdit}
                onCancel={() => router.push("/management/coaches")}
                isLoading={isUpdating}
                errorMessage={formError}
                showFooterActions={false}
              />
            )
          ) : (
            <CoachCreateForm
              key={firstBranchId}
              formId={FORM_ID}
              branches={branches}
              onSubmit={submit}
              onCancel={() => router.push("/management/coaches")}
              isLoading={isCreating}
              errorMessage={formError}
              showFooterActions={false}
            />
          )}
        </FormCard>
        <UploadBox compact className="entry-form-card" />
      </div>
    </ManagementCreatePage>
  );
}
