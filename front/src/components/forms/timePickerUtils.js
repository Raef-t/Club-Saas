export function cleanTimeInput(raw) {
  return String(raw || "")
    .replace(/\D/g, "")
    .slice(0, 4);
}

export function applyTimeMask(digits) {
  const hour = digits.slice(0, 2);
  const minute = digits.slice(2, 4);
  return digits.length <= 2 ? hour : `${hour}:${minute}`;
}

/**
 * Parses a masked 24-hour time and rejects invalid hours and minutes.
 */
export function parseTimeInput(masked) {
  const match = String(masked || "")
    .trim()
    .match(/^(\d{2}):(\d{2})$/);
  if (!match) return null;

  const hour = Number(match[1]);
  const minute = Number(match[2]);
  if (hour > 23 || minute > 59) return null;

  return `${String(hour).padStart(2, "0")}:${String(minute).padStart(2, "0")}`;
}
