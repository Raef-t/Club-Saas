"use client";

import { useState } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import Button from "@/components/ui/Button";
import AccountCredentialsDialog from "@/components/ui/AccountCredentialsDialog";
import ManagementCreatePage from "@/components/forms/ManagementCreatePage";
import { FormCard, UploadBox } from "@/components/forms/FormControls";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import StaffForm from "../StaffForm";
import { useStaff } from "../useStaff";
import { extractCreatedAccount } from "@/lib/generatedAccount";

const FORM_ID = "staff-form";

export default function StaffCreateClient() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const { selectedBranchId } = useManagementBranch();
  const editId = Number(searchParams.get("id"));
  const isEdit = searchParams.get("mode") === "edit" && Number.isFinite(editId) && editId > 0;
  const [photo, setPhoto] = useState([]);
  const [createdCredentials, setCreatedCredentials] = useState(null);
  const {
    branches,
    formError,
    isCreating,
    isUpdating,
    detailsError,
    refetchDetails,
    handleCreate,
    handleUpdate,
    getEditInitialValues,
  } = useStaff({
    selectedStaffId: isEdit ? editId : null,
    fetchDetails: isEdit,
  });
  const editInitialValues = isEdit ? getEditInitialValues() : null;

  async function submit(values) {
    const response = await handleCreate({ ...values, photo: photo[0] || null });
    if (!response) return;

    const created = extractCreatedAccount(response, { entityKeys: ["staff"] });
    const { username, password } = created;

    if (username || password) {
      setCreatedCredentials({
        id: created.id,
        username,
        password,
      });
      return;
    }

    router.push("/management/staff");
  }

  async function submitEdit(values) {
    const response = await handleUpdate(values);
    if (response) router.push("/management/staff");
  }

  function closeCredentialsDialog() {
    setCreatedCredentials(null);
    router.push("/management/staff");
  }

  return (
    <>
      <ManagementCreatePage
        title={isEdit ? "تعديل موظف" : "إضافة موظف"}
        subtitle={isEdit ? "إدارة الموظفين > تعديل موظف" : "إدارة الموظفين > إضافة موظف"}
        formId={FORM_ID}
        backHref="/management/staff"
        isSubmitting={isEdit ? isUpdating : isCreating}
        submitLabel={isEdit ? "حفظ التعديلات" : "إضافة الموظف"}
      >
        <div className={isEdit ? "mx-auto max-w-3xl" : "entry-form-side-layout"}>
          <FormCard title="البيانات الشخصية والوظيفية" className="entry-form-card p-5">
            {isEdit && detailsError ? (
              <div className="space-y-4 py-8 text-center">
                <p className="text-sm text-app-red">تعذر تحميل بيانات الموظف.</p>
                <Button tone="outline" onClick={refetchDetails}>
                  إعادة المحاولة
                </Button>
              </div>
            ) : isEdit && !editInitialValues ? (
              <p className="py-8 text-center text-sm text-app-muted-light">
                جاري تحميل بيانات الموظف...
              </p>
            ) : (
              <StaffForm
                key={isEdit ? `staff-edit-${editId}` : `staff-create-${selectedBranchId}`}
                formId={FORM_ID}
                branches={branches}
                initialValues={isEdit ? editInitialValues : null}
                onSubmit={isEdit ? submitEdit : submit}
                onCancel={() => router.push("/management/staff")}
                isLoading={isEdit ? isUpdating : isCreating}
                errorMessage={formError}
              />
            )}
          </FormCard>

          {!isEdit && (
            <UploadBox
              className="entry-form-card"
              label="صورة الموظف"
              subtitle="الصورة الشخصية (اختيارية)"
              accept=".png,.jpg,.jpeg"
              multiple={false}
              maxSizeMB={2}
              value={photo}
              onChange={setPhoto}
            />
          )}
        </div>
      </ManagementCreatePage>

      <AccountCredentialsDialog
        credentials={createdCredentials}
        entityLabel="الموظف"
        closeLabel="العودة إلى قائمة الموظفين"
        onClose={closeCredentialsDialog}
      />
    </>
  );
}
