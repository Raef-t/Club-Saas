/**
 * Duration helpers for offer subscription periods.
 *
 * Provides formatting, calculation, and preset utilities shared across
 * the offer form, details drawer, subscribe modal, and subscription form.
 */

/**
 * Converts a number of days into a human-readable Arabic duration string.
 *
 * @param {number|string} days
 * @returns {string} Formatted Arabic duration label (e.g. "شهر ونصف تقريباً")
 */
export function formatDurationDays(days) {
  const d = Number(days);
  if (!d || d <= 0 || !Number.isFinite(d)) return "";

  // Exact well-known durations
  if (d === 1) return "يوم واحد";
  if (d === 7) return "أسبوع واحد";
  if (d === 14) return "أسبوعين";
  if (d === 30) return "شهر واحد";
  if (d === 45) return "شهر ونصف تقريباً";
  if (d === 60) return "شهرين";
  if (d === 90) return "3 أشهر";
  if (d === 120) return "4 أشهر";
  if (d === 150) return "5 أشهر";
  if (d === 180) return "6 أشهر";
  if (d === 270) return "9 أشهر";
  if (d === 365) return "سنة كاملة";
  if (d === 730) return "سنتين";

  // Approximate calculation
  const months = d / 30;
  if (months < 1) return `${d} يوم`;
  if (Number.isInteger(months)) {
    if (months === 1) return "شهر واحد";
    if (months === 2) return "شهرين";
    if (months <= 10) return `${months} أشهر`;
    return `${months} شهر`;
  }
  return `${months.toFixed(1)} شهر تقريباً (${d} يوم)`;
}

/**
 * Calculates the end date by adding a number of days to a start date.
 *
 * @param {string} startDate  ISO date string (YYYY-MM-DD)
 * @param {number} durationDays
 * @returns {string} ISO date string (YYYY-MM-DD) or ""
 */
export function getEndDateFromDuration(startDate, durationDays) {
  const days = Number(durationDays);
  if (!startDate || !days || days <= 0) return "";

  const match = String(startDate).match(/^(\d{4})-(\d{2})-(\d{2})$/);
  if (!match) return "";

  const date = new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]));
  date.setDate(date.getDate() + days);

  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const day = String(date.getDate()).padStart(2, "0");
  return `${year}-${month}-${day}`;
}

/**
 * Quick-select presets for common subscription durations.
 */
export const DURATION_PRESETS = [
  { label: "شهر", days: 30 },
  { label: "شهر ونصف", days: 45 },
  { label: "3 أشهر", days: 90 },
  { label: "سنة", days: 365 },
];
