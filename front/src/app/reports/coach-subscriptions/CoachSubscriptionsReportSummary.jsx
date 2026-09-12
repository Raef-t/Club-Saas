import SectionCard from "@/components/ui/SectionCard";
import StatsGrid from "@/components/ui/StatsGrid";

export default function CoachSubscriptionsReportSummary({ summary, generalEquipment }) {
  const stats = [
    {
      title: "كوتشات الحصص الجماعية",
      value: summary.total_group_coaches.toLocaleString("ar"),
      helper: "إجمالي الكوتشات المسجلين",
      tone: "yellow",
      iconKey: "coaches",
    },
    {
      title: "لاعبي الحصص الجماعية",
      value: summary.total_group_active_players.toLocaleString("ar"),
      helper: "اللاعبون ذوو الاشتراكات الفعالة",
      tone: "green",
      iconKey: "members",
    },
    {
      title: "لاعبي الأجهزة العامة",
      value: summary.general_equipment_active_players.toLocaleString("ar"),
      helper: "اللاعبون الفعالون في التدريب العام",
      tone: "blue",
      iconKey: "generalTraining",
    },
  ];

  return (
    <div className="space-y-4">
      <StatsGrid items={stats} />
      <SectionCard
        title={generalEquipment.title}
        subtitle="ملخص اشتراكات التدريب على الأجهزة العامة"
        contentClassName="border-t border-app-line p-4"
      >
        <div className="grid gap-3 sm:grid-cols-2">
          <div className="rounded-xl border border-app-line bg-app-card-soft px-4 py-3">
            <span className="text-xs text-app-muted-light">نوع النشاط</span>
            <strong className="mt-2 block text-base font-medium text-app-text">
              {generalEquipment.activityTypeName}
            </strong>
          </div>
          <div className="rounded-xl border border-app-line bg-app-card-soft px-4 py-3">
            <span className="text-xs text-app-muted-light">اللاعبون الفعالون</span>
            <strong className="mt-2 block text-xl font-medium text-app-yellow">
              {generalEquipment.activePlayersCount.toLocaleString("ar")}
            </strong>
          </div>
        </div>
      </SectionCard>
    </div>
  );
}
