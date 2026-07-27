"use client";

import { useRouter } from "next/navigation";
import ManagementCreatePage from "@/components/forms/ManagementCreatePage";
import { FormCard } from "@/components/forms/FormControls";
import BranchForm from "../BranchForm";
import { getBranchCollection, getBranchRecord } from "../branchUtils";
import { useBranchEditor } from "./useBranchEditor";

const FORM_ID = "branch-editor-form";

/**
 * Composes the server-seeded branch create or edit workspace.
 */
export default function BranchesCreateClient({ mode, branchId, initialData }) {
  const router = useRouter();
  const isEdit = mode === "edit";
  const editor = useBranchEditor({ mode, branchId });
  const branch = getBranchRecord(initialData?.branch);
  const clubs = getBranchCollection(initialData?.clubs);

  /**
   * Saves the branch and returns to the list after a successful mutation.
   */
  async function handleSubmit(values) {
    const succeeded = await editor.submitBranch(values);
    if (succeeded) {
      router.push("/management/branches");
    }
  }

  /**
   * Returns to the branch list without saving.
   */
  function handleCancel() {
    router.push("/management/branches");
  }

  return (
    <ManagementCreatePage
      title={isEdit ? "تعديل فرع" : "إضافة فرع"}
      subtitle={isEdit ? "إدارة النادي > تعديل فرع" : "إدارة النادي > إضافة فرع"}
      formId={FORM_ID}
      backHref="/management/branches"
      isSubmitting={editor.isSubmitting}
      submitLabel={isEdit ? "حفظ التعديل" : "حفظ"}
    >
      <FormCard title="بيانات الفرع" className="entry-form-card p-5">
        <BranchForm
          key={isEdit ? `branch-edit-${branchId}` : "branch-create"}
          formId={FORM_ID}
          mode={mode}
          clubs={clubs}
          initialValues={branch}
          onSubmit={handleSubmit}
          onCancel={handleCancel}
          isLoading={editor.isSubmitting}
          errorMessage={editor.errorMessage}
        />
      </FormCard>
    </ManagementCreatePage>
  );
}
