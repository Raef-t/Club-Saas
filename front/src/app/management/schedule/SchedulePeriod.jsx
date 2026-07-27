import SectionCard from "@/components/ui/SectionCard";
import { ClockIcon } from "@/components/icons/Icons";
import ScheduleTable from "./ScheduleTable";

/**
 * Wraps a schedule table with its period summary and empty state.
 */
export default function SchedulePeriod({
  title,
  startTime,
  endTime,
  slots,
  scheduleData,
  periodKey,
}) {
  return (
    <SectionCard
      title={title}
      subtitle={`من ${startTime} إلى ${endTime} — ${slots.length} حصة`}
      action={
        <div className="flex items-center gap-1 text-xs">
          <ClockIcon className="size-3.5" />
          <span>
            {startTime} - {endTime}
          </span>
        </div>
      }
      contentClassName="px-5 pb-5"
    >
      {slots.length ? (
        <ScheduleTable
          title={title}
          slots={slots}
          scheduleData={scheduleData}
          periodKey={periodKey}
        />
      ) : (
        <div className="py-12 text-center text-sm text-app-muted">لا توجد حصص ضمن هذه الفترة.</div>
      )}
    </SectionCard>
  );
}
