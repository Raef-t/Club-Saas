/**
 * Displays one backend-provided schedule value without editing controls.
 */
export default function ScheduleCell({ value }) {
  return (
    <div className="schedule-cell-content">
      {value || <span className="schedule-cell-empty">—</span>}
    </div>
  );
}
