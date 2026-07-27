"use client";

import Button from "@/components/ui/Button";
import SectionCard from "@/components/ui/SectionCard";
import SkeletonPage from "@/components/ui/Skeleton";
import StatsGrid from "@/components/ui/StatsGrid";
import LineChart from "@/components/charts/LineChart";
import {
  DailyScheduleTable,
  DashboardQuickLinks,
  DashboardSectionLink,
  SubscriptionDonut,
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

      <section className="grid gap-5 xl:grid-cols-[minmax(0,1fr)_340px]">
        <SectionCard
          title="الحصص المجدولة أسبوعياً"
          subtitle="عدد الحصص في كل يوم حسب الفرع المختار"
          action={<DashboardSectionLink href="/management/schedule" />}
          className="min-h-[208px]"
        >
          <LineChart data={dashboard.scheduleChart} />
        </SectionCard>

        <SectionCard
          title="توزيع الاشتراكات"
          subtitle="حسب خطط الاشتراكات الفعالة"
          action={<DashboardSectionLink href="/management/subscriptions" />}
          className="min-h-[206px]"
        >
          <SubscriptionDonut items={dashboard.subscriptionMix} />
        </SectionCard>
      </section>

      <SectionCard
        title="جدول الدوام اليومي"
        subtitle="حصص اليوم مرتبة حسب وقت البداية"
        action={
          <DashboardSectionLink href="/management/schedule">
            فتح الجدول الأسبوعي
          </DashboardSectionLink>
        }
        className="min-h-[260px]"
        contentClassName="px-5 pb-5"
      >
        <DailyScheduleTable sessions={dashboard.todaySessions} />
      </SectionCard>

      <SectionCard
        title="الوصول السريع"
        subtitle="جميع صفحات نظام الإدارة"
        contentClassName="px-5 pb-5"
      >
        <DashboardQuickLinks />
      </SectionCard>
    </div>
  );
}
