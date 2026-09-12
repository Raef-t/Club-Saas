import FrozenTerminatedReportClient from "../../frozen-terminated/FrozenTerminatedReportClient";
import { verifyPageAccess } from "@/lib/server/auth";

export const metadata = {
  title: "تقرير الاشتراكات المجمدة والملغاة | TechnoGYM",
};

export default async function SubscriptionsFrozenTerminatedPage() {
  await verifyPageAccess("/reports/subscriptions");

  return <FrozenTerminatedReportClient />;
}
