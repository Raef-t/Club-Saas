"use client";

import { useRouter } from "next/navigation";
import ManagementCreatePage from "@/components/forms/ManagementCreatePage";
import { FormCard } from "@/components/forms/FormControls";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import LockerCreateForm from "../LockerCreateForm";
import { useCreateLocker } from "../useLockerFormMutation";
import { getLockerCollection } from "../lockerUtils";

const FORM_ID = "create-locker-form";

/**
 * Connects the create-locker form to its mutation and navigation.
 */
export default function LockersCreateClient({ initialBranches }) {
  const router = useRouter();
  const { selectedBranchId } = useManagementBranch();
  const { formError, isLoading, submitLocker } = useCreateLocker();
  const branches = getLockerCollection(initialBranches);

  /**
   * Creates the locker and returns to the list after success.
   */
  async function submit(values) {
    const succeeded = await submitLocker(values);
    if (succeeded) router.push("/management/lockers");
  }

  return (
    <ManagementCreatePage
      title="إضافة خزانة"
      subtitle="إدارة الخزائن > إضافة خزانة"
      formId={FORM_ID}
      backHref="/management/lockers"
      isSubmitting={isLoading}
      submitLabel="حفظ"
    >
      <div className="entry-form-side-layout">
        <FormCard title="بيانات الخزانة" className="entry-form-card p-5">
          <LockerCreateForm
            key={`locker-create-${selectedBranchId}`}
            formId={FORM_ID}
            branches={branches}
            onSubmit={submit}
            onCancel={() => router.push("/management/lockers")}
            isLoading={isLoading}
            errorMessage={formError}
          />
        </FormCard>
      </div>
    </ManagementCreatePage>
  );
}
