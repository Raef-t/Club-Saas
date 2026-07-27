import ScheduleClient from "@/app/management/schedule/ScheduleClient";
import { verifySession } from "@/lib/server/auth";
import { requestBackend } from "@/lib/server/backend";

export const metadata = {
  title: "جدول الدوام | TechnoGYM",
  description: "إدارة وتنظيم جدول دوام المدربين الأسبوعي مع إمكانية تخصيص الأوقات والطباعة",
};

export default async function SchedulePage() {
  const { token } = await verifySession();
  const schedule = await requestBackend("session-templates/schedule", {
    token,
  });

  return <ScheduleClient initialSchedule={schedule} />;
}
