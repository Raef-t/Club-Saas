import StatsGrid from "@/components/ui/StatsGrid";

export default function TimeCapacityReportSummary({ summary }) {
  const stats = [
    {
      title: "إجمالي الأنشطة",
      value: summary.total_activities.toLocaleString("ar"),
      helper: "الأنشطة ضمن النتائج الحالية",
      tone: "yellow",
      iconKey: "activities",
    },
    {
      title: "إجمالي الكوتشات",
      value: summary.total_coaches.toLocaleString("ar"),
      helper: "الكوتشات المسندون للحصص",
      tone: "blue",
      iconKey: "coaches",
    },
    {
      title: "إجمالي الخطط",
      value: summary.total_plans.toLocaleString("ar"),
      helper: "خطط الاشتراك المشمولة",
      tone: "purple",
      iconKey: "subscriptions",
    },
    {
      title: "المشتركون الفعالون",
      value: summary.total_active_subscribers.toLocaleString("ar"),
      helper: "المشتركون الحاليون في الحصص",
      tone: "green",
      iconKey: "members",
    },
  ];

  return <StatsGrid items={stats} />;
}
