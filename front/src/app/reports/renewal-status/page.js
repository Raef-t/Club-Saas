import RenewalStatusReportClient from "./RenewalStatusReportClient";
import { verifyPageAccess } from "@/lib/server/auth";

export const metadata = {
  title: "تقرير حالة تجديد الاشتراكات | TechnoGYM",
};

export default async function RenewalStatusReportPage() {
  await verifyPageAccess("/reports/renewal-status");

  return <RenewalStatusReportClient />;
}
