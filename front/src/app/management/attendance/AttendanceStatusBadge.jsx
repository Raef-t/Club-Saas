import { ATTENDANCE_STATUS_CLASSES } from "./attendanceConstants";

/**
 * Displays the visual state of one attendance record.
 */
export default function AttendanceStatusBadge({ status }) {
  const statusClass = ATTENDANCE_STATUS_CLASSES[status] || "bg-white/10 text-app-muted-light";

  return (
    <span
      className={`inline-flex min-w-16 justify-center rounded-lg px-3 py-1 text-xs font-medium ${statusClass}`}
    >
      {status}
    </span>
  );
}
