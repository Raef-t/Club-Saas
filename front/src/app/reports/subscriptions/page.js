import SubscriptionsReportClient from "./SubscriptionsReportClient";
import { verifyPageAccess } from "@/lib/server/auth";

export const metadata = {
  title: "التقرير الشامل للاشتراكات | TechnoGYM",
};

export default async function SubscriptionsReportPage() {
  await verifyPageAccess("/reports/subscriptions");

  return <SubscriptionsReportClient />;
}
