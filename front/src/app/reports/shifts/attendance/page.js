import ShiftAttendanceReportClient from "../../shift-attendance/ShiftAttendanceReportClient";
import { verifyPageAccess } from "@/lib/server/auth";

export const metadata = {
  title: "تقرير حضور وازدحام ورديات الأنشطة | TechnoGYM",
};

export default async function ShiftsAttendanceAliasPage() {
  await verifyPageAccess("/reports/shifts/attendance");

  return <ShiftAttendanceReportClient />;
}
