import ShiftAttendanceReportClient from "./ShiftAttendanceReportClient";
import { verifyPageAccess } from "@/lib/server/auth";

export const metadata = {
  title: "تقرير حضور وازدحام ورديات الأنشطة | TechnoGYM",
};

export default async function ShiftAttendanceReportPage() {
  await verifyPageAccess("/reports/shift-attendance");

  return <ShiftAttendanceReportClient />;
}
