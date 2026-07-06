"use client";

import { useRouter, useSearchParams } from "next/navigation";
import ManagementCreatePage from "@/components/forms/ManagementCreatePage";
import { FormCard, UploadBox } from "@/components/forms/FormControls";
import { ClubForm } from "../ClubsClient";
import { useClubs } from "../useClubs";

const FORM_ID = "create-club-form";

export default function ClubsCreateClient() {
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
  } = useClubs({ selectedClubId: isEdit ? Number(editId) : null });
  const editInitialValues = isEdit ? getEditInitialValues() : null;

  async function submit(values) {
    const ok = isEdit ? await handleUpdate(values) : await handleCreate(values);
    if (ok) router.push("/management/clubs");
  }

  return (
    <ManagementCreatePage
      title={isEdit ? "تعديل نادي" : "إضافة نادي"}
      subtitle={isEdit ? "إدارة النادي > تعديل نادي" : "إدارة النادي > إضافة نادي"}
      formId={FORM_ID}
      backHref="/management/clubs"
      isSubmitting={isEdit ? isUpdating : isCreating}
      submitLabel={isEdit ? "حفظ التعديل" : "حفظ"}
      maxWidth="760px"
    >
      <div className="entry-form-side-layout">
        <FormCard title="بيانات النادي" className="entry-form-card p-5">
          {isEdit && !editInitialValues ? (
            <p className="py-8 text-center text-sm text-app-muted-light">
              جاري تحميل بيانات النادي...
            </p>
          ) : (
            <ClubForm
              key={isEdit ? `club-edit-${editId}` : "club-create"}
              formId={FORM_ID}
              mode={isEdit ? "edit" : "create"}
              initialValues={editInitialValues || undefined}
              onSubmit={submit}
              onCancel={() => router.push("/management/clubs")}
              isLoading={isEdit ? isUpdating : isCreating}
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
