"use client";

import PageHeader from "@/components/common/PageHeader";
import Button from "@/components/ui/Button";
import SkeletonPage from "@/components/ui/Skeleton";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import SchedulePeriod from "./SchedulePeriod";
import SchedulePrintIcon from "./SchedulePrintIcon";
import ScheduleTips from "./ScheduleTips";
import { useSchedule } from "./useSchedule";
import "./schedule.css";

/**
 * Composes the read-only weekly schedule from backend data.
 */
export default function ScheduleClient({ initialSchedule }) {
  const { selectedBranchId } = useManagementBranch();

  return (
    <ScheduleWorkspace
      key={selectedBranchId}
      initialSchedule={initialSchedule}
      selectedBranchId={selectedBranchId}
    />
  );
}

/**
 * Isolates schedule data whenever the global branch changes.
 */
function ScheduleWorkspace({ initialSchedule, selectedBranchId }) {
  const schedule = useSchedule({ initialSchedule, selectedBranchId });

  if (schedule.isLoading) {
    return <SkeletonPage blocks={[{ type: "header" }, { type: "table", rows: 6, columns: 6 }]} />;
  }

  return (
    <div className="space-y-6">
      <PageHeader
        eyebrow="إدارة الجدول"
        title="جدول الدوام الأسبوعي"
        subtitle="عرض جدول المدربين والحصص القادم من النظام وطباعة نسخة أسبوعية."
        action={
          <Button
            type="button"
            onClick={schedule.handlePrint}
            icon={<SchedulePrintIcon className="size-4" />}
          >
            طباعة الجدول
          </Button>
        }
      />

      {schedule.scheduleErrorMessage && (
        <div
          className="flex flex-col gap-3 rounded-xl border border-app-red/30 bg-app-red/10 px-4 py-3 text-sm text-app-red sm:flex-row sm:items-center sm:justify-between"
          role="alert"
        >
          <p>{schedule.scheduleErrorMessage}</p>
          <Button
            type="button"
            tone="outline"
            className="h-9 px-3 text-xs"
            loading={schedule.isRefreshing}
            onClick={schedule.retrySchedule}
          >
            إعادة المحاولة
          </Button>
        </div>
      )}

      <SchedulePeriod
        holidayDayKeys={schedule.holidayDayKeys}
        title="الفترة الصباحية"
        startTime={schedule.settings.morningStart}
        endTime={schedule.settings.morningEnd}
        slots={schedule.morningSlots}
        scheduleData={schedule.scheduleData}
        periodKey="morning"
      />

      <SchedulePeriod
        holidayDayKeys={schedule.holidayDayKeys}
        title="الفترة المسائية"
        startTime={schedule.settings.eveningStart}
        endTime={schedule.settings.eveningEnd}
        slots={schedule.eveningSlots}
        scheduleData={schedule.scheduleData}
        periodKey="evening"
      />

      <ScheduleTips />
    </div>
  );
}
