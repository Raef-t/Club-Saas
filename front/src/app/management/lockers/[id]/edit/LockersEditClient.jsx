"use client";

import { useRouter } from "next/navigation";
import ManagementCreatePage from "@/components/forms/ManagementCreatePage";
import { FormCard } from "@/components/forms/FormControls";
import { LockerUpdateForm } from "../../LockersClient";
import { useGetLockerQuery, useUpdateLockerMutation } from "@/lib/api/lockersApi";
import { useToast } from "@/components/ui/Toast";
import SkeletonPage from "@/components/ui/Skeleton";

const FORM_ID = "edit-locker-form";

export default function LockersEditClient({ lockerId }) {
  const router = useRouter();
  const toast = useToast();
  
  const { data: lockerData, isLoading: isFetching } = useGetLockerQuery(lockerId);
  const locker = lockerData?.data;

  const [updateLocker, { isLoading: isUpdating }] = useUpdateLockerMutation();

  async function submit(values) {
    try {
      await updateLocker({ id: lockerId, ...values }).unwrap();
      toast.success("تم تعديل الخزانة بنجاح!");
      router.push("/management/lockers");
    } catch (error) {
      toast.error(error?.data?.message || "تعذر تعديل الخزانة.");
    }
  }

  if (isFetching) {
    return <SkeletonPage />;
  }

  if (!locker) {
    return (
      <div className="flex flex-col items-center justify-center p-10 text-center">
        <h2 className="text-xl font-bold text-white mb-2">الخزانة غير موجودة</h2>
        <button className="text-app-yellow underline" onClick={() => router.push("/management/lockers")}>
          العودة لقائمة الخزائن
        </button>
      </div>
    );
  }

  return (
    <ManagementCreatePage
      title={`تعديل خزانة: ${locker.locker_number}`}
      subtitle="إدارة الخزائن > تعديل خزانة"
      formId={FORM_ID}
      backHref="/management/lockers"
      isSubmitting={isUpdating}
      submitLabel="حفظ التعديلات"
    >
      <div className="entry-form-side-layout">
        <FormCard title="بيانات الخزانة" className="entry-form-card p-5">
          <LockerUpdateForm
            formId={FORM_ID}
            initialData={locker}
            onSubmit={submit}
            onCancel={() => router.push("/management/lockers")}
            isLoading={isUpdating}
          />
        </FormCard>
      </div>
    </ManagementCreatePage>
  );
}
