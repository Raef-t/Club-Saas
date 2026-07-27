"use client";

import { useRouter } from "next/navigation";
import ManagementCreatePage from "@/components/forms/ManagementCreatePage";
import { FormCard } from "@/components/forms/FormControls";
import ClubForm from "../ClubForm";
import { getClubCollection, getClubRecord } from "../clubUtils";
import { useClubEditor } from "./useClubEditor";

const FORM_ID = "club-editor-form";

/**
 * Composes the server-seeded club create or edit workspace.
 */
export default function ClubsCreateClient({ mode, clubId, initialData }) {
  const router = useRouter();
  const isEdit = mode === "edit";
  const club = getClubRecord(initialData?.club);
  const clubs = getClubCollection(initialData?.clubs);
  const editor = useClubEditor({
    mode,
    clubId,
    existingClubs: clubs,
  });

  /**
   * Saves the club and returns to the list after a successful mutation.
   */
  async function handleSubmit(values) {
    const succeeded = await editor.submitClub(values);
    if (succeeded) {
      router.push("/management/clubs");
    }
  }

  /**
   * Returns to the club list without saving.
   */
  function handleCancel() {
    router.push("/management/clubs");
  }

  return (
    <ManagementCreatePage
      title={isEdit ? "تعديل نادي" : "إضافة نادي"}
      subtitle={isEdit ? "إدارة النادي > تعديل نادي" : "إدارة النادي > إضافة نادي"}
      formId={FORM_ID}
      backHref="/management/clubs"
      isSubmitting={editor.isSubmitting}
      submitLabel={isEdit ? "حفظ التعديل" : "حفظ"}
    >
      <FormCard title="بيانات النادي" className="entry-form-card p-5">
        <ClubForm
          key={isEdit ? `club-edit-${clubId}` : "club-create"}
          formId={FORM_ID}
          mode={mode}
          initialValues={club}
          onSubmit={handleSubmit}
          onCancel={handleCancel}
          isLoading={editor.isSubmitting}
          errorMessage={editor.errorMessage}
        />
      </FormCard>
    </ManagementCreatePage>
  );
}
