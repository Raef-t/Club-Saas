import { SCHEDULE_DAYS } from "./scheduleConstants";
import ScheduleCell from "./ScheduleCell";
import { useTimeFormat } from "@/lib/TimeFormatContext";

/**
 * Renders one read-only weekly schedule period.
 */
export default function ScheduleTable({ title, slots, scheduleData, periodKey }) {
  const { formatTime } = useTimeFormat();

  if (!slots.length) return null;

  return (
    <div className="schedule-wrapper schedule-table-enter">
      <table className="schedule-table">
        <caption>{title}</caption>
        <thead>
          <tr>
            <th className="day-cell">اليوم</th>
            {slots.map((slot) => (
              <th key={slot.key}>
                <div className="flex flex-col items-center gap-0.5 leading-tight">
                  <span>{formatTime(slot.from)}</span>
                  <span className="text-[10px] text-app-muted-light" aria-hidden="true">
                    ↔
                  </span>
                  <span>{formatTime(slot.to)}</span>
                </div>
              </th>
            ))}
          </tr>
        </thead>
        <tbody>
          {SCHEDULE_DAYS.map((day) => (
            <tr key={day.key}>
              <td className="day-cell">{day.label}</td>
              {slots.map((slot) => {
                const cellKey = `${periodKey}_${slot.key}`;

                return (
                  <td
                    key={`${day.key}-${slot.key}`}
                    className="schedule-cell"
                    data-label={`${formatTime(slot.from)} - ${formatTime(slot.to)}`}
                  >
                    <ScheduleCell value={scheduleData?.[day.key]?.[cellKey] || ""} />
                  </td>
                );
              })}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
