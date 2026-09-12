import TimeCapacityReportClient from "./TimeCapacityReportClient";
import { verifyPageAccess } from "@/lib/server/auth";

export const metadata = {
  title: "تقرير سعة الحصص حسب الوقت | TechnoGYM",
};

export default async function TimeCapacityReportPage() {
  await verifyPageAccess("/reports/time-capacity");

  return <TimeCapacityReportClient />;
}
