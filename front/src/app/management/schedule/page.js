import ScheduleClient from "@/app/management/schedule/ScheduleClient";
import { verifyPageAccess } from "@/lib/server/auth";
import { safeRequestBackend } from "@/lib/server/backend";

export const metadata = {
  title: "جدول الدوام | TechnoGYM",
  description: "إدارة وتنظيم جدول دوام المدربين الأسبوعي مع إمكانية تخصيص الأوقات والطباعة",
};

export default async function SchedulePage() {
  const { token } = await verifyPageAccess("/management/schedule");
  const schedule = await safeRequestBackend(
    "session-templates/schedule",
    {
      token,
    },
    null,
  );

  return <ScheduleClient initialSchedule={schedule} />;
}
