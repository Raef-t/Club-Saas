import { ATTENDANCE_STATUS_CLASSES } from "./attendanceConstants";

/**
 * Displays the visual state of one attendance record.
 */
export default function AttendanceStatusBadge({ status, interactive = false }) {
  const statusClass = ATTENDANCE_STATUS_CLASSES[status] || "bg-white/10 text-app-muted-light";

  return (
    <span
      className={`inline-flex min-w-16 items-center justify-center gap-1 rounded-lg px-3 py-1 text-xs font-medium ${statusClass} ${interactive ? "ring-1 ring-transparent transition hover:ring-app-yellow/60" : ""}`}
    >
      {status}
      {interactive && <span aria-hidden="true">⌄</span>}
    </span>
  );
}
