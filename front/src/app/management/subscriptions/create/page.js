import SubscriptionsCreateClient from "./SubscriptionsCreateClient";
import { verifySession } from "@/lib/server/auth";
import { requestBackend } from "@/lib/server/backend";

export const metadata = {
  title: "إدارة الاشتراك | TechnoGYM",
};

export default async function CreateSubscriptionPage() {
  const { token } = await verifySession();
  const [members, plans, activities, coaches] = await Promise.all([
    requestBackend("members", { token }),
    requestBackend("subscription-plans", { token }),
    requestBackend("activities", { token }),
    requestBackend("coaches", { token }),
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
