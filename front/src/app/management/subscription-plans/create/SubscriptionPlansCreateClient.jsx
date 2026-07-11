"use client";

import { useRouter, useSearchParams } from "next/navigation";
import ManagementCreatePage from "@/components/forms/ManagementCreatePage";
import { FormCard } from "@/components/forms/FormControls";
import { PlanForm } from "../SubscriptionPlansClient";
import { useSubscriptionPlans } from "../useSubscriptionPlans";

const FORM_ID = "create-subscription-plan-form";

export default function SubscriptionPlansCreateClient() {
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
    branches,
    activities,
    coaches,
  } = useSubscriptionPlans({ selectedPlanId: isEdit ? Number(editId) : null });
  const editInitialValues = isEdit ? getEditInitialValues() : null;

  async function submit(values) {
    const ok = isEdit ? await handleUpdate(values) : await handleCreate(values);
    if (ok) router.push("/management/subscription-plans");
  }

  return (
    <ManagementCreatePage
      title={isEdit ? "تعديل فعالية" : "إضافة فعالية"}
      subtitle={isEdit ? "إدارة النادي > تعديل فعالية" : "إدارة النادي > إضافة فعالية"}
      formId={FORM_ID}
      backHref="/management/subscription-plans"
      isSubmitting={isEdit ? isUpdating : isCreating}
      submitLabel={isEdit ? "حفظ التعديل" : "حفظ"}
      maxWidth="720px"
    >
      <FormCard title="تفاصيل الفعالية" className="entry-form-card p-5">
        {isEdit && !editInitialValues ? (
          <p className="py-8 text-center text-sm text-app-muted-light">
            جاري تحميل بيانات الفعالية...
          </p>
        ) : (
          <PlanForm
            key={isEdit ? `plan-edit-${editId}` : "plan-create"}
            formId={FORM_ID}
            mode={isEdit ? "edit" : "create"}
            initialValues={editInitialValues || undefined}
            onSubmit={submit}
            onCancel={() => router.push("/management/subscription-plans")}
            isLoading={isEdit ? isUpdating : isCreating}
            errorMessage={formError}
            showFooterActions={false}
            branches={branches}
            activities={activities}
            coaches={coaches}
          />
        )}
      </FormCard>
    </ManagementCreatePage>
  );
}
