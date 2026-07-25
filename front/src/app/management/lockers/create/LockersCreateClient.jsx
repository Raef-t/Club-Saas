"use client";

import { useRouter } from "next/navigation";
import ManagementCreatePage from "@/components/forms/ManagementCreatePage";
import { FormCard } from "@/components/forms/FormControls";
import { LockerCreateForm } from "../LockersClient";
import { useLockers } from "../useLockers";
import { useGetBranchesQuery } from "@/lib/api/branchesApi";
import { getBranchesArray } from "@/lib/utils";

const FORM_ID = "create-locker-form";

export default function LockersCreateClient() {
  const router = useRouter();
  
  const {
    formError,
    isCreating,
    handleCreate,
  } = useLockers();

  const { data: branchesData } = useGetBranchesQuery({});
  const branches = getBranchesArray(branchesData);

  async function submit(values) {
    const ok = await handleCreate(values);
    if (ok) router.push("/management/lockers");
  }

  return (
    <ManagementCreatePage
      title="إضافة خزانة"
      subtitle="إدارة الخزائن > إضافة خزانة"
      formId={FORM_ID}
      backHref="/management/lockers"
      isSubmitting={isCreating}
      submitLabel="حفظ"
    >
      <div className="entry-form-side-layout">
        <FormCard title="بيانات الخزانة" className="entry-form-card p-5">
          <LockerCreateForm
            formId={FORM_ID}
            branches={branches}
            onSubmit={submit}
            onCancel={() => router.push("/management/lockers")}
            isLoading={isCreating}
            errorMessage={formError}
          />
        </FormCard>
      </div>
    </ManagementCreatePage>
  );
}
