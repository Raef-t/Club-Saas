import { SCHEDULE_DAYS } from "./scheduleConstants";
import { getEntityBranchIds } from "../../../lib/managementBranchUtils";

const VALID_TIME_PATTERN = /^(?:[01]\d|2[0-3]):[0-5]\d$/;
const DAY_BY_API_KEY = Object.fromEntries(SCHEDULE_DAYS.map((day) => [day.apiKey, day.key]));

/**
 * Pads a time segment with a leading zero.
 */
function padTimePart(value) {
  return String(value).padStart(2, "0");
}

/**
 * Converts a valid clock value into minutes from the start of the day.
 */
function getTimeInMinutes(value) {
  if (!VALID_TIME_PATTERN.test(value || "")) return null;
  const [hours, minutes] = value.split(":").map(Number);
  return hours * 60 + minutes;
}

/**
 * Generates complete fixed-duration slots, including periods that cross midnight.
 */
export function generateTimeSlots(startTime, endTime, stepMinutes) {
  const start = getTimeInMinutes(startTime);
  const end = getTimeInMinutes(endTime);
  const step = Number(stepMinutes);

  if (start === null || end === null || !Number.isFinite(step) || step <= 0) {
    return [];
  }

  const adjustedEnd = end <= start ? end + 24 * 60 : end;
  const slots = [];

  for (let current = start; current + step <= adjustedEnd && slots.length < 96; current += step) {
    const next = current + step;
    const fromHours = Math.floor(current / 60) % 24;
    const fromMinutes = current % 60;
    const toHours = Math.floor(next / 60) % 24;
    const toMinutes = next % 60;

    slots.push({
      key: `${padTimePart(fromHours)}${padTimePart(fromMinutes)}`,
      from: `${padTimePart(fromHours)}:${padTimePart(fromMinutes)}`,
      to: `${padTimePart(toHours)}:${padTimePart(toMinutes)}`,
      label: `${padTimePart(fromHours)}:${padTimePart(fromMinutes)}`,
    });
  }

  return slots;
}

/**
 * Returns the schedule payload from the supported backend response shapes.
 */
function getSchedulePayload(response) {
  const payload = response?.data?.data || response?.data || response;
  return payload && typeof payload === "object" && !Array.isArray(payload) ? payload : {};
}

/**
 * Returns a localized display name without introducing a placeholder.
 */
function getDisplayName(value) {
  if (!value) return "";
  if (typeof value === "string") return value;
  return value.ar || value.en || "";
}

/**
 * Maps backend session templates into the matching local period and time cells.
 */
export function createScheduleDataFromApi(
  response,
  morningSlots,
  eveningSlots,
  selectedBranchId = "all",
) {
  const payload = getSchedulePayload(response);
  const morningKeys = new Set(morningSlots.map((slot) => slot.key));
  const eveningKeys = new Set(eveningSlots.map((slot) => slot.key));
  const scheduleData = {};

  for (const [apiDay, sessions] of Object.entries(payload)) {
    const localDay = DAY_BY_API_KEY[apiDay];
    if (!localDay || !Array.isArray(sessions)) continue;

    for (const session of sessions) {
      const branchIds = getEntityBranchIds(session);
      const belongsToSelectedBranch =
        !selectedBranchId ||
        selectedBranchId === "all" ||
        branchIds.length === 0 ||
        branchIds.includes(String(selectedBranchId));

      if (!belongsToSelectedBranch) continue;

      const [hours, minutes] = String(session.start_time || "").split(":");
      if (hours === undefined || minutes === undefined) continue;

      const slotKey = `${padTimePart(hours)}${padTimePart(minutes)}`;
      const planName = getDisplayName(session.plan_name || session.plan?.name);
      const coachName = getDisplayName(
        session.coach?.name || session.coach?.person?.full_name || session.coach_name,
      );
      const cellValue = [planName, coachName].filter(Boolean).join(" - ");

      if (!scheduleData[localDay]) scheduleData[localDay] = {};
      if (morningKeys.has(slotKey)) {
        scheduleData[localDay][`morning_${slotKey}`] = cellValue;
      }
      if (eveningKeys.has(slotKey)) {
        scheduleData[localDay][`evening_${slotKey}`] = cellValue;
      }
    }
  }

  return scheduleData;
}

/**
 * Escapes schedule values before placing them inside printable HTML.
 */
export function escapeScheduleHtml(value) {
  return String(value ?? "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}
