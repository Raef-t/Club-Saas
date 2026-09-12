import PeakHoursReportClient from "./PeakHoursReportClient";
import { verifyPageAccess } from "@/lib/server/auth";

export const metadata = {
  title: "تقرير ساعات الذروة والانخفاض | TechnoGYM",
};

export default async function PeakHoursReportPage() {
  await verifyPageAccess("/reports/peak-hours");

  return <PeakHoursReportClient />;
}
