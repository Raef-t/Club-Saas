import { verifyPageAccess } from "@/lib/server/auth";
import CoachSubscriptionsReportClient from "./CoachSubscriptionsReportClient";

export const metadata = {
  title: "تقرير كوتشات الحصص والأجهزة العامة | TechnoGYM",
};

export default async function CoachSubscriptionsReportPage() {
  await verifyPageAccess("/reports/coach-subscriptions");

  return <CoachSubscriptionsReportClient />;
}
