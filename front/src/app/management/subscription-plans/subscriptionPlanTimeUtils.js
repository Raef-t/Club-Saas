const CLOCK_TIME_PATTERN = /^(?:[01]\d|2[0-3]):[0-5]\d$/;

/**
 * Adds a duration to a 24-hour clock value and wraps across midnight.
 */
export function addMinutesToTime(time, minutesToAdd = 60) {
  if (!CLOCK_TIME_PATTERN.test(time || "")) return "";

  const [hours, minutes] = time.split(":").map(Number);
  const totalMinutes = (hours * 60 + minutes + minutesToAdd) % (24 * 60);
  const endHours = Math.floor(totalMinutes / 60);
  const endMinutes = totalMinutes % 60;

  return `${String(endHours).padStart(2, "0")}:${String(endMinutes).padStart(2, "0")}`;
}
