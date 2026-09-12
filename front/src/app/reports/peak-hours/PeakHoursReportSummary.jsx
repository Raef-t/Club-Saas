import StatsGrid from "@/components/ui/StatsGrid";

export default function PeakHoursReportSummary({ summary }) {
  const stats = [
    {
      title: "الحضور المحلل",
      value: summary.total_attendances_analyzed.toLocaleString("ar"),
      helper: "إجمالي عمليات الدخول المحتسبة",
      tone: "yellow",
      iconKey: "members",
    },
    {
      title: "أكثر الأيام ازدحاماً",
      value: summary.busiest_day,
      helper: "حسب إجمالي تسجيلات الدخول",
      tone: "green",
      iconKey: "schedule",
    },
    {
      title: "أهدأ الأيام",
      value: summary.quietest_day,
      helper: "أقل أيام الأسبوع حركة",
      tone: "blue",
      iconKey: "schedule",
    },
    {
      title: "نطاق وقت الذروة",
      value: summary.peak_hours_range,
      helper: "أعلى فترة حضوراً",
      tone: "orange",
      iconKey: "schedule",
    },
    {
      title: "نطاق الوقت الهادئ",
      value: summary.off_peak_hours_range,
      helper: "أقل فترة حضوراً",
      tone: "purple",
      iconKey: "schedule",
    },
    {
      title: "العطل المستبعدة",
      value: summary.excluded_holidays_count.toLocaleString("ar"),
      helper: "لم تدخل في حساب التحليل",
      tone: "cyan",
      iconKey: "schedule",
    },
  ];

  return <StatsGrid items={stats} />;
}
