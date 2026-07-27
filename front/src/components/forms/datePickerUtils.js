/**
 * Converts a date to the ISO date format used by form values.
 */
export function toIsoDate(date) {
  if (!date) return "";
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const day = String(date.getDate()).padStart(2, "0");
  return `${year}-${month}-${day}`;
}

/**
 * Parses an ISO date without allowing JavaScript date rollover.
 */
export function fromIsoDate(iso) {
  if (!iso) return null;
  const [year, month, day] = String(iso).split("-").map(Number);
  if (!year || !month || !day) return null;

  const date = new Date(year, month - 1, day);
  return date.getFullYear() === year && date.getMonth() === month - 1 && date.getDate() === day
    ? date
    : null;
}

/**
 * Formats an ISO date for a supported display format.
 */
export function formatDateDisplay(iso, format = "DD/MM/YYYY") {
  const date = fromIsoDate(iso);
  if (!date) return "";

  const day = String(date.getDate()).padStart(2, "0");
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const year = date.getFullYear();

  if (format === "YYYY/MM/DD") return `${year}/${month}/${day}`;
  return format === "MM/DD/YYYY" ? `${month}/${day}/${year}` : `${day}/${month}/${year}`;
}

export function cleanDateInput(raw) {
  return String(raw || "")
    .replace(/\D/g, "")
    .slice(0, 8);
}

export function applyDateMask(digits, format = "DD/MM/YYYY") {
  if (format === "YYYY/MM/DD") {
    const year = digits.slice(0, 4);
    const month = digits.slice(4, 6);
    const day = digits.slice(6, 8);
    if (digits.length <= 4) return year;
    if (digits.length <= 6) return `${year}/${month}`;
    return `${year}/${month}/${day}`;
  }

  const first = digits.slice(0, 2);
  const second = digits.slice(2, 4);
  const year = digits.slice(4, 8);
  if (digits.length <= 2) return first;
  if (digits.length <= 4) return `${first}/${second}`;
  return `${first}/${second}/${year}`;
}

/**
 * Parses a masked date and rejects impossible calendar dates.
 */
export function parseDateInput(masked, format = "DD/MM/YYYY") {
  const value = String(masked || "").trim();
  const match =
    format === "YYYY/MM/DD"
      ? value.match(/^(\d{4})\/(\d{2})\/(\d{2})$/)
      : value.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
  if (!match) return null;

  const year = Number(format === "YYYY/MM/DD" ? match[1] : match[3]);
  const first = Number(format === "YYYY/MM/DD" ? match[2] : match[1]);
  const second = Number(format === "YYYY/MM/DD" ? match[3] : match[2]);
  const month = format === "DD/MM/YYYY" ? second : first;
  const day = format === "DD/MM/YYYY" ? first : second;

  if (year < 1900 || year > 2100 || month < 1 || month > 12 || day < 1 || day > 31) {
    return null;
  }

  const date = new Date(year, month - 1, day);
  return fromIsoDate(toIsoDate(date))?.getTime() === date.getTime() &&
    date.getFullYear() === year &&
    date.getMonth() === month - 1 &&
    date.getDate() === day
    ? toIsoDate(date)
    : null;
}
