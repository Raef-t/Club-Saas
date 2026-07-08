"use client";

import { useRouter, useSearchParams } from "next/navigation";
import ManagementCreatePage from "@/components/forms/ManagementCreatePage";
import { FormCard } from "@/components/forms/FormControls";
import { ActivityForm } from "../ActivitiesClient";
import { useActivities } from "../useActivities";

const FORM_ID = "create-activity-form";

export default function ActivitiesCreateClient() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const mode = searchParams.get("mode");
  const editId = searchParams.get("id");
  const isEdit = mode === "edit" && editId;
  const {
    formError,
    isCreating,
    isUpdating,
    handleCreate,
    handleUpdate,
    getEditInitialValues,
    branches,
    activityTypes,
  } = useActivities({ selectedActivityId: isEdit ? Number(editId) : null });

  async function submit(values) {
    const ok = isEdit ? await handleUpdate(values) : await handleCreate(values);
    if (ok) router.push("/management/activities");
  }

  const editInitialValues = isEdit ? getEditInitialValues() : null;

  return (
    <ManagementCreatePage
      title={isEdit ? "تعديل نشاط" : "إضافة نشاط"}
      subtitle={isEdit ? "إدارة الأعضاء > تعديل نشاط" : "إدارة الأعضاء > إضافة نشاط"}
      formId={FORM_ID}
      backHref="/management/activities"
      isSubmitting={isEdit ? isUpdating : isCreating}
      submitLabel={isEdit ? "حفظ التعديل" : "حفظ"}
      maxWidth="720px"
    >
      <FormCard title="معلومات النشاط" className="entry-form-card p-5">
        {isEdit && !editInitialValues ? (
          <p className="py-8 text-center text-sm text-app-muted-light">
            جاري تحميل بيانات النشاط...
          </p>
        ) : (
          <ActivityForm
            key={isEdit ? `activity-edit-${editId}` : "activity-create"}
            formId={FORM_ID}
            mode={isEdit ? "edit" : "create"}
            initialValues={editInitialValues || undefined}
            branches={branches}
            activityTypes={activityTypes}
            onSubmit={submit}
            onCancel={() => router.push("/management/activities")}
            isLoading={isEdit ? isUpdating : isCreating}
            errorMessage={formError}
            showFooterActions={false}
          />
        )}
      </FormCard>
    </ManagementCreatePage>
  );
}
