import ScheduleClient from "@/app/management/schedule/ScheduleClient";

export const metadata = {
  title: "جدول الدوام | TechnoGYM",
  description: "إدارة وتنظيم جدول دوام المدربين الأسبوعي مع إمكانية تخصيص الأوقات والطباعة",
};

export default function SchedulePage() {
  return <ScheduleClient />;
}
