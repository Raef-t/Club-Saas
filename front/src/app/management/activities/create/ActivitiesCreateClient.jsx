"use client";

import { useRouter } from "next/navigation";
import ManagementCreatePage from "@/components/forms/ManagementCreatePage";
import { FormCard } from "@/components/forms/FormControls";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import ActivityForm from "../ActivityForm";
import { getActivityCollection, getActivityRecord } from "../activityUtils";
import { useActivityEditor } from "./useActivityEditor";

const FORM_ID = "activity-editor-form";

/**
 * Composes the server-seeded activity create or edit workspace.
 */
export default function ActivitiesCreateClient({ mode, activityId, initialData }) {
  const router = useRouter();
  const { selectedBranchId } = useManagementBranch();
  const isEdit = mode === "edit";
  const editor = useActivityEditor({ mode, activityId });
  const activity = getActivityRecord(initialData?.activity);
  const branches = getActivityCollection(initialData?.branches);
  const activityTypes = getActivityCollection(initialData?.activityTypes);

  /**
   * Saves the activity and returns to the list after a successful mutation.
   */
  async function handleSubmit(values) {
    const succeeded = await editor.submitActivity(values);
    if (succeeded) {
      router.push("/management/activities");
    }
  }

  /**
   * Returns to the activity list without saving.
   */
  function handleCancel() {
    router.push("/management/activities");
  }

  return (
    <ManagementCreatePage
      title={isEdit ? "تعديل نشاط" : "إضافة نشاط"}
      subtitle={isEdit ? "الأنشطة الرياضية > تعديل نشاط" : "الأنشطة الرياضية > إضافة نشاط"}
      formId={FORM_ID}
      backHref="/management/activities"
      isSubmitting={editor.isSubmitting}
      submitLabel={isEdit ? "حفظ التعديل" : "حفظ"}
    >
      <FormCard title="معلومات النشاط" className="entry-form-card p-5">
        <ActivityForm
          key={isEdit ? `activity-edit-${activityId}` : `activity-create-${selectedBranchId}`}
          formId={FORM_ID}
          mode={mode}
          initialValues={activity}
          initialShifts={initialData?.shifts}
          branches={branches}
          activityTypes={activityTypes}
          onSubmit={handleSubmit}
          onCancel={handleCancel}
          isLoading={editor.isSubmitting}
          errorMessage={editor.errorMessage}
        />
      </FormCard>
    </ManagementCreatePage>
  );
}
