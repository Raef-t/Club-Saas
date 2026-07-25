"use client";

import { useRouter, useSearchParams } from "next/navigation";
import ManagementCreatePage from "@/components/forms/ManagementCreatePage";
import { FormCard } from "@/components/forms/FormControls";
import { BranchForm, branchInitialForm } from "../BranchesClient";
import { useBranches } from "../useBranches";

const FORM_ID = "create-branch-form";

export default function BranchesCreateClient() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const mode = searchParams.get("mode");
  const editId = searchParams.get("id");
  const isEdit = mode === "edit" && editId;
  const {
    clubs,
    formError,
    isCreating,
    isUpdating,
    handleCreate,
    handleUpdate,
    getEditInitialValues,
  } = useBranches({ selectedBranchId: isEdit ? Number(editId) : null });
  const firstClubId = clubs[0]?.id ? String(clubs[0].id) : "";
  const editInitialValues = isEdit ? getEditInitialValues() : null;

  async function submit(values) {
    const ok = isEdit ? await handleUpdate(values) : await handleCreate(values);
    if (ok) router.push("/management/branches");
  }

  return (
    <ManagementCreatePage
      title={isEdit ? "تعديل فرع" : "إضافة فرع"}
      subtitle={isEdit ? "إدارة النادي > تعديل فرع" : "إدارة النادي > إضافة فرع"}
      formId={FORM_ID}
      backHref="/management/branches"
      submitLabel={isEdit ? "حفظ التعديل" : "حفظ"}
    >
      <FormCard title="بيانات الفرع" className="entry-form-card p-5">
        {isEdit && !editInitialValues ? (
          <p className="py-8 text-center text-sm text-app-muted-light">
            جاري تحميل بيانات الفرع...
          </p>
        ) : (
          <BranchForm
            key={isEdit ? `branch-edit-${editId}` : firstClubId || "branch-create"}
            formId={FORM_ID}
            mode={isEdit ? "edit" : "create"}
            clubs={clubs}
            initialValues={
              editInitialValues || { ...branchInitialForm, club_id: firstClubId }
            }
            onSubmit={submit}
            onCancel={() => router.push("/management/branches")}
            isLoading={isEdit ? isUpdating : isCreating}
            errorMessage={formError}
          />
        )}
      </FormCard>
    </ManagementCreatePage>
  );
}
