"use client";

import { useRouter } from "next/navigation";
import ManagementCreatePage from "@/components/forms/ManagementCreatePage";
import { FormCard } from "@/components/forms/FormControls";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { SubscriptionCreateForm } from "../SubscriptionForm";
import { useCreateSubscription } from "../useCreateSubscription";

const FORM_ID = "create-subscription-form";

/**
 * Connects the create-subscription form to navigation and its mutation hook.
 */
export default function SubscriptionsCreateClient({ initialData }) {
  const router = useRouter();
  const { selectedBranchId } = useManagementBranch();
  const { members, plans, activities, coaches, formError, isCreating, handleCreateSubscription } =
    useCreateSubscription({ initialData });

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
    >
      <FormCard title="تفاصيل الاشتراك" className="entry-form-card p-5">
        <SubscriptionCreateForm
          key={`${selectedBranchId}-${members[0]?.id || "member"}-${plans[0]?.id || "plan"}`}
          formId={FORM_ID}
          members={members}
          plans={plans}
          activities={activities}
          coaches={coaches}
          onSubmit={submit}
          onCancel={() => router.push("/management/subscriptions")}
          isLoading={isCreating}
          errorMessage={formError}
        />
      </FormCard>
    </ManagementCreatePage>
  );
}
