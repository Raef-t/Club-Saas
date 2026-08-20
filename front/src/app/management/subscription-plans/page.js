import { Suspense } from "react";
import SubscriptionPlansClient from "@/app/management/subscription-plans/SubscriptionPlansClient";
import { verifySession } from "@/lib/server/auth";
import { requestBackend } from "@/lib/server/backend";

export const metadata = {
  title: "خطط الاشتراك | TechnoGYM",
};

export default async function SubscriptionPlansPage() {
  const { token } = await verifySession();
  const [plans, branches, activities, coaches] = await Promise.all([
    requestBackend("subscription-plans", { token }),
    requestBackend("branches", { token }),
    requestBackend("activities", { token }),
    requestBackend("coaches", { token }),
  ]);

  return (
    <Suspense fallback={null}>
      <SubscriptionPlansClient initialData={{ plans, branches, activities, coaches }} />
    </Suspense>
  );
}
