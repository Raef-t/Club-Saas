import { Suspense } from "react";
import SubscriptionPlansCreateClient from "./SubscriptionPlansCreateClient";
import { verifyPageAccess } from "@/lib/server/auth";

export default async function CreateSubscriptionPlanPage() {
  await verifyPageAccess("/management/subscription-plans/create");

  return (
    <Suspense fallback={null}>
      <SubscriptionPlansCreateClient />
    </Suspense>
  );
}
