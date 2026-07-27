import { formatLocalizedName } from "../../../lib/utils";

export const DAY_IN_MILLISECONDS = 24 * 60 * 60 * 1000;

/**
 * Extracts a collection from the response shapes returned by the backend.
 */
export function getReportCollection(response) {
  if (Array.isArray(response?.data?.data)) return response.data.data;
  if (Array.isArray(response?.data)) return response.data;
  if (Array.isArray(response)) return response;
  return [];
}

/**
 * Resolves a localized value without returning the generic dash placeholder.
 */
export function getDisplayName(value, fallback = "-") {
  const name = formatLocalizedName(value);
  return name === "-" ? fallback : name;
}

/**
 * Resolves the member or coach name used across printable reports.
 */
export function getPersonName(entity) {
  const member = entity?.member || entity?.attendable || entity?.player || entity;
  const person = member?.person || entity?.person || {};
  const combinedName = `${member?.first_name || ""} ${member?.last_name || ""}`.trim();
  return person.full_name || entity?.member_name || combinedName || "-";
}

/**
 * Resolves the branch name attached to an entity.
 */
export function getBranchName(entity) {
  return getDisplayName(
    entity?.branch?.name ||
      entity?.member?.branch?.name ||
      entity?.player?.branch?.name ||
      entity?.plan?.branch?.name ||
      entity?.branch_name,
  );
}

/**
 * Parses a backend date while keeping date-only values in the local calendar day.
 */
export function parseReportDate(value) {
  if (!value) return null;
  const normalizedValue = /^\d{4}-\d{2}-\d{2}$/.test(String(value)) ? `${value}T00:00:00` : value;
  const date = new Date(normalizedValue);
  return Number.isNaN(date.getTime()) ? null : date;
}

/**
 * Formats a report date for Arabic print output.
 */
export function formatReportDate(value) {
  const date = parseReportDate(value);
  if (!date) return "-";

  return date.toLocaleDateString("ar-EG", {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
}

/**
 * Formats a check-in time for the hall occupancy report.
 */
export function formatReportTime(value) {
  const date = parseReportDate(value);
  if (!date) return "-";

  return date
    .toLocaleTimeString("en-US", {
      hour: "2-digit",
      minute: "2-digit",
      hour12: true,
    })
    .toLowerCase();
}

/**
 * Resolves a subscription status consistently from its status and end date.
 */
export function resolveSubscriptionStatus(subscription, now) {
  const status = String(subscription?.status || "").toLowerCase();
  const endDate = parseReportDate(subscription?.end_date);

  if (status === "active" && endDate && endDate < now) return "expired";
  if (status) return status;
  return endDate && endDate < now ? "expired" : "active";
}

/**
 * Checks whether a subscription is currently active.
 */
export function isActiveSubscription(subscription, now) {
  return resolveSubscriptionStatus(subscription, now) === "active";
}
