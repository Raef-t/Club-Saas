"use client";

import { useState } from "react";
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
    activities,
    formError,
    isCreating,
    isUpdating,
    handleCreate,
    handleUpdate,
    getEditInitialValues,
  } = useCoaches({ selectedCoachId: isEdit ? Number(editId) : null });
  const firstBranchId = branches[0]?.id || "none";
  const editInitialValues = isEdit ? getEditInitialValues() : null;

  const [photo, setPhoto] = useState([]);

  async function submit(values) {
    const payload = {
      ...values,
      photo: photo[0] || null,
    };
    const ok = await handleCreate(payload);
    if (ok) router.push("/management/coaches");
  }

  async function submitEdit(basicValues, detailsValues) {
    const payloadBasic = {
      ...basicValues,
      photo: photo[0] || null,
    };
    const ok = await handleUpdate(payloadBasic, detailsValues);
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
              activities={activities}
              onSubmit={submit}
              onCancel={() => router.push("/management/coaches")}
              isLoading={isCreating}
              errorMessage={formError}
              showFooterActions={false}
            />
          )}
        </FormCard>
        <UploadBox
          className="entry-form-card"
          label="صورة المدرب"
          subtitle="الصورة الشخصية للمدرب"
          accept=".png,.jpg,.jpeg"
          multiple={false}
          value={photo}
          onChange={setPhoto}
        />
      </div>
    </ManagementCreatePage>
  );
}
