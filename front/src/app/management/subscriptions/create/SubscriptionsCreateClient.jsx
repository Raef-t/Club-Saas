"use client";

import { useRouter } from "next/navigation";
import ManagementCreatePage from "@/components/forms/ManagementCreatePage";
import { FormCard } from "@/components/forms/FormControls";
import { SubscriptionCreateForm } from "../SubscriptionsClient";
import { useSubscriptions } from "../useSubscriptions";

const FORM_ID = "create-subscription-form";

export default function SubscriptionsCreateClient() {
  const router = useRouter();
  const {
    members,
    plans,
    activities,
    coaches,
    formError,
    isCreating,
    handleCreateSubscription,
  } = useSubscriptions();

  async function submit(values) {
    const ok = await handleCreateSubscription(values);
    if (ok) router.push("/management/subscriptions");
  }

  return (
    <ManagementCreatePage
      title="إضافة اشتراك"
      subtitle="إدارة الأعضاء > إضافة اشتراك"
      formId={FORM_ID}
      backHref="/management/subscriptions"
      isSubmitting={isCreating}
      maxWidth="920px"
    >
      <FormCard title="تفاصيل الاشتراك" className="entry-form-card p-5">
        <SubscriptionCreateForm
          key={`${members[0]?.id || "member"}-${plans[0]?.id || "plan"}`}
          formId={FORM_ID}
          members={members}
          plans={plans}
          activities={activities}
          coaches={coaches}
          onSubmit={submit}
          onCancel={() => router.push("/management/subscriptions")}
          isLoading={isCreating}
          errorMessage={formError}
          showFooterActions={false}
        />
      </FormCard>
    </ManagementCreatePage>
  );
}
