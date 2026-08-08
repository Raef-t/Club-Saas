"use client";

import { useState } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import ManagementCreatePage from "@/components/forms/ManagementCreatePage";
import { FormCard } from "@/components/forms/FormControls";
import Button from "@/components/ui/Button";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { SubscriptionCreateForm, SubscriptionEditForm } from "../SubscriptionForm";
import { useCreateSubscription } from "../useCreateSubscription";

const FORM_ID = "create-subscription-form";

/**
 * Connects the create-subscription form to navigation and its mutation hook.
 */
export default function SubscriptionsCreateClient({ initialData }) {
  const router = useRouter();
  const searchParams = useSearchParams();
  const initialMemberId = searchParams.get("memberId") || "";
  const editId = Number(searchParams.get("id"));
  const isEdit = searchParams.get("mode") === "edit" && Number.isFinite(editId) && editId > 0;
  const { selectedBranchId } = useManagementBranch();

  const [resetKey, setResetKey] = useState(0);
  const [currentMemberId, setCurrentMemberId] = useState(initialMemberId);

  const {
    members,
    plans,
    activities,
    coaches,
    selectedSubscription,
    subscriptionDetailError,
    isSubscriptionDetailLoading,
    isSubscriptionDetailFetching,
    refetchSubscriptionDetail,
    formError,
    isCreating,
    isUpdating,
    handleCreateSubscription,
    handleUpdateSubscription,
  } = useCreateSubscription({
    initialData,
    selectedSubscriptionId: isEdit ? editId : null,
  });

  const currentMember = selectedSubscription?.member;
  const editMembers =
    currentMember?.id && !members.some((member) => String(member.id) === String(currentMember.id))
      ? [...members, currentMember]
      : members;
  const currentPlan = selectedSubscription?.plan;
  const editPlans =
    currentPlan?.id && !plans.some((plan) => String(plan.id) === String(currentPlan.id))
      ? [...plans, currentPlan]
      : plans;

  async function submit(values, action) {
    const ok = await handleCreateSubscription(values);
    if (ok) {
      if (action === "addAnother") {
        setCurrentMemberId(values.member_id);
        setResetKey((prev) => prev + 1);
      } else {
        router.push("/management/subscriptions");
      }
    }
  }

  async function submitEdit(values) {
    const ok = await handleUpdateSubscription(values);
    if (ok) router.push("/management/subscriptions");
  }

  return (
    <ManagementCreatePage
      title={isEdit ? "تعديل اشتراك" : "إضافة اشتراك"}
      subtitle={isEdit ? "إدارة الاشتراكات > تعديل اشتراك" : "إدارة الأعضاء > إضافة اشتراك"}
      formId={FORM_ID}
      backHref="/management/subscriptions"
      isSubmitting={isEdit ? isUpdating : isCreating}
      submitLabel={isEdit ? "حفظ التعديلات" : "إنشاء الاشتراك"}
    >
      <FormCard
        title={isEdit ? "تعديل تفاصيل الاشتراك" : "تفاصيل الاشتراك"}
        className="entry-form-card p-5"
      >
        {isEdit ? (
          subscriptionDetailError ? (
            <div className="space-y-4 py-8 text-center">
              <p className="text-sm text-app-red">تعذر تحميل بيانات الاشتراك.</p>
              <Button
                type="button"
                tone="outline"
                className="h-10 px-4"
                onClick={refetchSubscriptionDetail}
              >
                إعادة المحاولة
              </Button>
            </div>
          ) : isSubscriptionDetailLoading || isSubscriptionDetailFetching ? (
            <p className="py-8 text-center text-sm text-app-muted-light">
              جاري تحميل بيانات الاشتراك...
            </p>
          ) : selectedSubscription ? (
            <SubscriptionEditForm
              key={`subscription-edit-${editId}`}
              subscription={selectedSubscription}
              members={editMembers}
              plans={editPlans}
              onSubmit={submitEdit}
              onCancel={() => router.push("/management/subscriptions")}
              isLoading={isUpdating}
              errorMessage={formError}
            />
          ) : null
        ) : (
          <SubscriptionCreateForm
            key={`${selectedBranchId}-${members[0]?.id || "member"}-${plans[0]?.id || "plan"}-${resetKey}`}
            formId={FORM_ID}
            initialMemberId={currentMemberId}
            members={members}
            plans={plans}
            activities={activities}
            coaches={coaches}
            onSubmit={submit}
            onCancel={() => router.push("/management/subscriptions")}
            isLoading={isCreating}
            errorMessage={formError}
          />
        )}
      </FormCard>
    </ManagementCreatePage>
  );
}
