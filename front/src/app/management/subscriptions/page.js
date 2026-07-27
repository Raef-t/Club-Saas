import SubscriptionsClient from "@/app/management/subscriptions/SubscriptionsClient";
import { verifySession } from "@/lib/server/auth";
import { requestBackend } from "@/lib/server/backend";

export const metadata = {
  title: "اشتراكات الأعضاء | TechnoGYM",
};

export default async function ManagementSubscriptionsPage() {
  const { token } = await verifySession();
  const [subscriptions, branches] = await Promise.all([
    requestBackend("player-subscriptions", { token }),
    requestBackend("branches", { token }),
  ]);

  return <SubscriptionsClient initialData={{ subscriptions, branches }} />;
}
