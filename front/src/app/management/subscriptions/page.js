import { Suspense } from "react";
import SubscriptionsClient from "@/app/management/subscriptions/SubscriptionsClient";
import { verifyPageAccess } from "@/lib/server/auth";
import { requestBackend, safeRequestBackend } from "@/lib/server/backend";

export const metadata = {
  title: "اشتراكات الأعضاء | TechnoGYM",
};

export default async function ManagementSubscriptionsPage() {
  const { token } = await verifyPageAccess("/management/subscriptions");
  const [subscriptions, branches] = await Promise.all([
    requestBackend("player-subscriptions", { token }),
    safeRequestBackend("branches", { token, params: { per_page: "all" } }, []),
  ]);

  return (
    <Suspense fallback={null}>
      <SubscriptionsClient initialData={{ subscriptions, branches }} />
    </Suspense>
  );
}
