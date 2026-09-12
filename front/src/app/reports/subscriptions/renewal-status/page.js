import RenewalStatusReportClient from "../../renewal-status/RenewalStatusReportClient";
import { verifyPageAccess } from "@/lib/server/auth";

export const metadata = {
  title: "تقرير حالة تجديد الاشتراكات | TechnoGYM",
};

export default async function SubscriptionsRenewalStatusReportPage() {
  await verifyPageAccess("/reports/subscriptions");

  return <RenewalStatusReportClient />;
}
