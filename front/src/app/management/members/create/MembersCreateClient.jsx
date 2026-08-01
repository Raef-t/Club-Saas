"use client";

import { useRouter, useSearchParams } from "next/navigation";
import ManagementCreatePage from "@/components/forms/ManagementCreatePage";
import { FormCard, UploadBox } from "@/components/forms/FormControls";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { MemberForm, memberInitialForm } from "../MemberForm";
import { useMembers } from "../useMembers";

const FORM_ID = "create-member-form";

export default function MembersCreateClient() {
  const router = useRouter();
  const { selectedBranchId } = useManagementBranch();
  const searchParams = useSearchParams();
  const mode = searchParams.get("mode");
  const editId = searchParams.get("id");
  const isEdit = mode === "edit" && editId;
  const {
    branches,
    plans,
    formError,
    isCreating,
    isUpdating,
    handleCreate,
    handleUpdate,
    getEditInitialValues,
  } = useMembers({ selectedMemberId: isEdit ? Number(editId) : null });

  const editInitialValues = isEdit ? getEditInitialValues() : null;

  async function submit(values) {
    if (isEdit) {
      const ok = await handleUpdate(values);
      if (ok) router.push("/management/members");
    } else {
      const memberId = await handleCreate(values);
      if (memberId) {
        router.push(`/management/subscriptions/create?memberId=${memberId}`);
      }
    }
  }

  return (
    <ManagementCreatePage
      title={isEdit ? "تعديل عضو" : "إضافة عضو"}
      subtitle={isEdit ? "إدارة الأعضاء > تعديل عضو" : "إدارة الأعضاء > إضافة عضو"}
      formId={FORM_ID}
      backHref="/management/members"
      isSubmitting={isEdit ? isUpdating : isCreating}
      submitLabel={isEdit ? "حفظ التعديل" : "حفظ"}
    >
      <div className="entry-form-side-layout">
        <FormCard title="البيانات الشخصية" className="entry-form-card p-5">
          {isEdit && !editInitialValues ? (
            <p className="py-8 text-center text-sm text-app-muted-light">
              جاري تحميل بيانات العضو...
            </p>
          ) : (
            <MemberForm
              key={isEdit ? `member-edit-${editId}` : `member-create-${selectedBranchId}`}
              formId={FORM_ID}
              mode={isEdit ? "edit" : "create"}
              branches={branches}
              plans={plans}
              initialValues={editInitialValues || memberInitialForm}
              onSubmit={submit}
              onCancel={() => router.push("/management/members")}
              isLoading={isEdit ? isUpdating : isCreating}
              errorMessage={formError}
            />
          )}
        </FormCard>
        <UploadBox compact className="entry-form-card" maxSizeMB={2} />
      </div>
    </ManagementCreatePage>
  );
}
