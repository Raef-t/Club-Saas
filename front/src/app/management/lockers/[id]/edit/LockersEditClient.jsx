"use client";

import { useRouter } from "next/navigation";
import ManagementCreatePage from "@/components/forms/ManagementCreatePage";
import { FormCard } from "@/components/forms/FormControls";
import LockerUpdateForm from "../../LockerUpdateForm";
import { useUpdateLocker } from "../../useLockerFormMutation";
import { getLockerCollection, getLockerRecord } from "../../lockerUtils";

const FORM_ID = "edit-locker-form";

/**
 * Connects the server-loaded locker form to its update mutation.
 */
export default function LockersEditClient({
  lockerId,
  initialLocker,
  initialMembers,
  initialCoaches,
  initialStaff,
}) {
  const router = useRouter();
  const locker = getLockerRecord(initialLocker);
  const members = getLockerCollection(initialMembers);
  const coaches = getLockerCollection(initialCoaches);
  const staff = getLockerCollection(initialStaff);
  const { formError, isLoading, submitLocker } = useUpdateLocker(lockerId);

  /**
   * Updates the locker and returns to the list after success.
   */
  async function submit(values) {
    const succeeded = await submitLocker(values);
    if (succeeded) router.push("/management/lockers");
  }

  return (
    <ManagementCreatePage
      title={`تعديل خزانة: ${locker.locker_number}`}
      subtitle="إدارة الخزائن > تعديل خزانة"
      formId={FORM_ID}
      backHref="/management/lockers"
      isSubmitting={isLoading}
      submitLabel="حفظ التعديلات"
    >
      <div className="entry-form-side-layout">
        <FormCard title="بيانات الخزانة" className="entry-form-card p-5">
          <LockerUpdateForm
            formId={FORM_ID}
            initialData={locker}
            members={members}
            coaches={coaches}
            staff={staff}
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
