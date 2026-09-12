import StatsGrid from "@/components/ui/StatsGrid";
import SectionCard from "@/components/ui/SectionCard";

export default function ShiftAttendanceReportSummary({ summary }) {
  const kpiItems = [
    {
      title: "الفترة المعروضة",
      value: summary.period_label || "غير محدد",
      helper: "نطاق التقرير الحالي",
      tone: "yellow",
      iconKey: "attendance",
    },
    {
      title: "إجمالي مرات الحضور",
      value: summary.total_shift_attendances.toLocaleString("ar"),
      helper: "حضور اللاعبين في الورديات",
      tone: "green",
      iconKey: "members",
    },
    {
      title: "عدد الورديات المشمولة",
      value: summary.total_shifts_count.toLocaleString("ar"),
      helper: "ورديات مطابقة للتصفية",
      tone: "blue",
      iconKey: "activities",
    },
  ];

  const busiest = summary.busiest_shift;
  const quietest = summary.quietest_shift;

  return (
    <div className="space-y-4">
      <StatsGrid items={kpiItems} />

      <div className="grid gap-4 md:grid-cols-2">
        <SectionCard
          title="الوردية الأكثر ازدحاماً (🔥 الذروة)"
          subtitle="الوردية التي شهدت أعلى نسبة إقبال وحضور للاعبين"
          contentClassName="p-5 border-t border-app-line"
        >
          {busiest ? (
            <div className="space-y-3">
              <div className="flex items-center justify-between">
                <span className="text-lg font-bold text-app-text">
                  {busiest.shift_name || "وردية بدون اسم"}
                </span>
                <span className="rounded-full border border-app-yellow/40 bg-app-yellow/10 px-3 py-1 text-xs font-bold text-app-yellow">
                  {busiest.crowd_percentage != null
                    ? `${Number(busiest.crowd_percentage).toLocaleString("ar", {
                        maximumFractionDigits: 1,
                      })}% ازدحام`
                    : "ذروة"}
                </span>
              </div>
              <div className="flex items-baseline justify-between text-xs text-app-muted-light">
                <span>عدد الحضور المسجل:</span>
                <strong className="text-sm font-semibold text-app-text">
                  {(busiest.attended_players_count || 0).toLocaleString("ar")} لاعب
                </strong>
              </div>
            </div>
          ) : (
            <p className="text-sm text-app-muted">لا توجد بيانات كافية لتحديد وردية الذروة.</p>
          )}
        </SectionCard>

        <SectionCard
          title="الوردية الأقل ازدحاماً (🌱 الهدوء)"
          subtitle="الوردية ذات الإقبال الأدنى ومعدلات الحضور الهادئة"
          contentClassName="p-5 border-t border-app-line"
        >
          {quietest ? (
            <div className="space-y-3">
              <div className="flex items-center justify-between">
                <span className="text-lg font-bold text-app-text">
                  {quietest.shift_name || "وردية بدون اسم"}
                </span>
                <span className="rounded-full border border-cyan-500/40 bg-cyan-950/20 px-3 py-1 text-xs font-bold text-cyan-300">
                  {quietest.crowd_percentage != null
                    ? `${Number(quietest.crowd_percentage).toLocaleString("ar", {
                        maximumFractionDigits: 1,
                      })}% ازدحام`
                    : "هدوء"}
                </span>
              </div>
              <div className="flex items-baseline justify-between text-xs text-app-muted-light">
                <span>عدد الحضور المسجل:</span>
                <strong className="text-sm font-semibold text-app-text">
                  {(quietest.attended_players_count || 0).toLocaleString("ar")} لاعب
                </strong>
              </div>
            </div>
          ) : (
            <p className="text-sm text-app-muted">لا توجد بيانات كافية لتحديد الوردية الهادئة.</p>
          )}
        </SectionCard>
      </div>
    </div>
  );
}
