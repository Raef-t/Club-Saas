"use client";

import Button from "@/components/ui/Button";
import SectionCard from "@/components/ui/SectionCard";
import SkeletonPage from "@/components/ui/Skeleton";
import StatsGrid from "@/components/ui/StatsGrid";
import BarChart from "@/components/charts/BarChart";
import {
  CoachSubscriptionsDonut,
  CurrentActiveSessionsTable,
  DailyScheduleTable,
  DashboardSectionLink,
} from "./ManagementDashboardWidgets";
import { useManagementDashboard } from "./useManagementDashboard";

/**
 * Composes the live management statistics and navigation dashboard.
 */
export default function ManagementDashboard({ initialData }) {
  const dashboard = useManagementDashboard({ initialData });

  if (dashboard.isLoading) {
    return (
      <SkeletonPage
        blocks={[
          { type: "stats", count: 5 },
          { type: "cards", count: 2 },
          { type: "table", rows: 4, columns: 5 },
        ]}
      />
    );
  }

  return (
    <div className="space-y-5" dir="rtl">
      {dashboard.hasError && (
        <div
          className="flex flex-col gap-3 rounded-xl border border-app-yellow/30 bg-app-yellow/10 px-4 py-3 text-sm text-app-muted-light sm:flex-row sm:items-center sm:justify-between"
          role="alert"
        >
          <p>تعذر تحديث بعض الإحصائيات. يتم عرض آخر بيانات متاحة.</p>
          <Button
            type="button"
            tone="outline"
            className="h-9 px-3 text-xs"
            loading={dashboard.isRefreshing}
            onClick={dashboard.refresh}
          >
            إعادة المحاولة
          </Button>
        </div>
      )}

      <StatsGrid items={dashboard.stats} variant="compact" />

      {/* جدول الدوام اليومي بدل الفعاليات الجارية */}
      <SectionCard
        title="جدول الدوام اليومي"
        subtitle="حصص اليوم مرتبة حسب وقت البداية"
        action={
          <DashboardSectionLink href="/management/schedule">
            فتح الجدول الأسبوعي
          </DashboardSectionLink>
        }
        className="min-h-[180px]"
        contentClassName="px-5 pb-5"
      >
        <DailyScheduleTable sessions={dashboard.todaySessions} />
      </SectionCard>

      <section className="grid gap-5 xl:grid-cols-[minmax(0,1fr)_340px]">
        <SectionCard
          title="أكثر الورديات ازدحاماً"
          subtitle="أعلى 7 ورديات نشاطاً حسب نسبة الحضور الفعلي"
          action={<DashboardSectionLink href="/reports">عرض التقارير</DashboardSectionLink>}
          className="min-h-[208px]"
        >
          <BarChart data={dashboard.shiftChart} />
        </SectionCard>

        <SectionCard
          title="اشتراكات الكوتشات"
          subtitle="عدد اللاعبين النشطين المسجلين مع كل كوتش"
          action={
            <DashboardSectionLink href="/management/coaches">عرض الكوتشات</DashboardSectionLink>
          }
          className="min-h-[230px]"
        >
          <CoachSubscriptionsDonut
            items={dashboard.coachSubscriptionMix}
            isLoading={dashboard.isCoachSubscriptionsLoading}
            hasError={dashboard.hasCoachSubscriptionsError}
          />
        </SectionCard>
      </section>
    </div>
  );
}
