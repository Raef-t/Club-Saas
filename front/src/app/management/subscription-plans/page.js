import { Suspense } from "react";
import SubscriptionPlansClient from "@/app/management/subscription-plans/SubscriptionPlansClient";
import { verifyPageAccess } from "@/lib/server/auth";
import { requestBackend, safeRequestBackend } from "@/lib/server/backend";

export const metadata = {
  title: "خطط الاشتراك | TechnoGYM",
};

export default async function SubscriptionPlansPage() {
  const { token } = await verifyPageAccess("/management/subscription-plans");
  const [plans, branches, activities, coaches] = await Promise.all([
    requestBackend("subscription-plans", { token }),
    safeRequestBackend("branches", { token, params: { per_page: "all" } }, []),
    safeRequestBackend("activities", { token, params: { per_page: "all" } }, []),
    safeRequestBackend("coaches", { token, params: { per_page: "all" } }, []),
  ]);

  return (
    <Suspense fallback={null}>
      <SubscriptionPlansClient initialData={{ plans, branches, activities, coaches }} />
    </Suspense>
  );
}
