"use client";

import { useState, useEffect } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import ManagementCreatePage from "@/components/forms/ManagementCreatePage";
import AccountCredentialsDialog from "@/components/ui/AccountCredentialsDialog";
import { FormCard, UploadBox } from "@/components/forms/FormControls";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { CoachCreateForm } from "../CoachForm";
import { useCoaches } from "../useCoaches";
import { extractCreatedAccount } from "@/lib/generatedAccount";

const FORM_ID = "create-coach-form";

export default function CoachesCreateClient() {
  const router = useRouter();
  const { selectedBranchId } = useManagementBranch();
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
  } = useCoaches({
    selectedCoachId: isEdit ? Number(editId) : null,
    fetchDetails: !!isEdit,
  });
  const editInitialValues = isEdit ? getEditInitialValues() : null;

  const [photo, setPhoto] = useState([]);
  const [createdCredentials, setCreatedCredentials] = useState(null);

  const initialPhoto = editInitialValues?.photo;
  useEffect(() => {
    if (initialPhoto) {
      setPhoto([initialPhoto]);
    }
  }, [initialPhoto]);

  async function submit(values) {
    const payload = {
      ...values,
      photo: photo[0] || null,
    };
    const response = await handleCreate(payload);
    if (!response) return;

    const account = extractCreatedAccount(response, { entityKeys: ["coach", "staff"] });
    if (account.username || account.password) {
      setCreatedCredentials(account);
      return;
    }

    router.push("/management/coaches");
  }

  async function submitEdit(values) {
    const payload = {
      ...values,
      photo: photo[0] || null,
    };
    const ok = await handleUpdate(payload);
    if (ok) router.push("/management/coaches");
  }

  function closeCredentialsDialog() {
    setCreatedCredentials(null);
    router.push("/management/coaches");
  }

  return (
    <>
      <ManagementCreatePage
        title={isEdit ? "تعديل مدرب" : "إضافة مدرب"}
        subtitle={isEdit ? "إدارة المدربين > تعديل مدرب" : "إدارة المدربين > إضافة مدرب"}
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
                <CoachCreateForm
                  key={`coach-edit-${editId}-${editInitialValues.country_code}-${editInitialValues.phone_number}`}
                  formId={FORM_ID}
                  initialValues={editInitialValues}
                  branches={branches}
                  activities={activities}
                  onSubmit={submitEdit}
                  onCancel={() => router.push("/management/coaches")}
                  isLoading={isUpdating}
                  errorMessage={formError}
                />
              )
            ) : (
              <CoachCreateForm
                key={`coach-create-${selectedBranchId}`}
                formId={FORM_ID}
                branches={branches}
                activities={activities}
                onSubmit={submit}
                onCancel={() => router.push("/management/coaches")}
                isLoading={isCreating}
                errorMessage={formError}
              />
            )}
          </FormCard>
          <UploadBox
            className="entry-form-card"
            label="صورة المدرب"
            subtitle="الصورة الشخصية للمدرب"
            accept=".png,.jpg,.jpeg"
            multiple={false}
            maxSizeMB={2}
            value={photo}
            onChange={setPhoto}
          />
        </div>
      </ManagementCreatePage>

      <AccountCredentialsDialog
        credentials={createdCredentials}
        entityLabel="المدرب"
        closeLabel="العودة إلى قائمة المدربين"
        onClose={closeCredentialsDialog}
      />
    </>
  );
}
