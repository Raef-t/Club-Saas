import SubscriptionsCreateClient from "./SubscriptionsCreateClient";
import { verifyPageAccess } from "@/lib/server/auth";
import { safeRequestBackend } from "@/lib/server/backend";

export const metadata = {
  title: "إدارة الاشتراك | TechnoGYM",
};

export default async function CreateSubscriptionPage() {
  const { token } = await verifyPageAccess("/management/subscriptions/create");
  const [members, plans, activities, coaches] = await Promise.all([
    safeRequestBackend("members", { token, params: { per_page: "all" } }, []),
    safeRequestBackend("subscription-plans", { token, params: { per_page: "all" } }, []),
    safeRequestBackend("activities", { token, params: { per_page: "all" } }, []),
    safeRequestBackend("coaches", { token, params: { per_page: "all" } }, []),
  ]);

  return (
    <SubscriptionsCreateClient
      initialData={{
        members,
        plans,
        activities,
        coaches,
      }}
    />
  );
}
