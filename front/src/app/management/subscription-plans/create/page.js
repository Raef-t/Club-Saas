import { Suspense } from "react";
import SubscriptionPlansCreateClient from "./SubscriptionPlansCreateClient";

export default function CreateSubscriptionPlanPage() {
  return (
    <Suspense fallback={null}>
      <SubscriptionPlansCreateClient />
    </Suspense>
  );
}
