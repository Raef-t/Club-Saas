const PAYROLL_ACTION_PATH = "/management/payroll";
const PAYROLL_NOTIFICATION_TYPES = new Set([
  "payroll_due",
  "payroll_generated",
  "payroll_ready",
  "payroll_run_generated",
  "payslip_due",
  "payslips_generated",
]);
const PAYROLL_TEXT_PATTERN = /رواتب|راتب|مسير\s+الرواتب|payroll|payslips?/i;

/**
 * Unwraps the common notification collection envelopes returned by the API.
 */
export function getNotifications(response) {
  let current = response;

  for (let depth = 0; depth < 5; depth += 1) {
    if (Array.isArray(current)) return current;
    if (!current || typeof current !== "object") return [];

    const next = current.notifications ?? current.items ?? current.data;
    if (next === undefined || next === current) return [];
    current = next;
  }

  return Array.isArray(current) ? current : [];
}

/**
 * Converts a payroll notification into an internal application action.
 * The backend action_url may supply missing metadata only when it is a relative,
 * allowlisted payroll path; it is never used as the navigation destination.
 */
export function getNotificationAction(notification) {
  const snapshot = getTargetSnapshot(notification);
  const actionUrl = getPayrollActionUrl(snapshot?.action_url);
  const notificationType = String(snapshot?.type || "").toLowerCase();
  const text = `${notification?.title || ""} ${notification?.preview || ""}`;
  const hasPayrollMetadata = Boolean(snapshot?.period_start || snapshot?.period_end);
  const isPayrollNotification =
    PAYROLL_NOTIFICATION_TYPES.has(notificationType) ||
    Boolean(actionUrl) ||
    (hasPayrollMetadata && PAYROLL_TEXT_PATTERN.test(text));

  if (!isPayrollNotification) return null;

  const branchId = Number(snapshot?.branch_id ?? actionUrl?.searchParams.get("branch_id"));
  if (!Number.isInteger(branchId) || branchId <= 0) return null;

  const params = new URLSearchParams({
    payroll_action: "generate",
    branch_id: String(branchId),
  });

  if (notification?.notification_id != null) {
    params.set("notification_id", String(notification.notification_id));
  }
  if (snapshot.period_start) params.set("period_start", String(snapshot.period_start));
  if (snapshot.period_end) params.set("period_end", String(snapshot.period_end));

  return {
    type: "payroll_due",
    branchId: String(branchId),
    href: `${PAYROLL_ACTION_PATH}?${params.toString()}`,
  };
}

function getTargetSnapshot(notification) {
  const rawSnapshot =
    notification?.target_snapshot ??
    notification?.targetSnapshot ??
    notification?.data?.target_snapshot ??
    null;

  if (rawSnapshot && typeof rawSnapshot === "object") return rawSnapshot;
  if (typeof rawSnapshot !== "string") return null;

  try {
    const parsed = JSON.parse(rawSnapshot);
    return parsed && typeof parsed === "object" ? parsed : null;
  } catch {
    return null;
  }
}

function getPayrollActionUrl(actionUrl) {
  if (typeof actionUrl !== "string" || !actionUrl.startsWith("/")) return null;

  try {
    const parsed = new URL(actionUrl, "http://internal.local");
    return /\/(payroll|payslips?|salar(?:y|ies))(\/|$)/i.test(parsed.pathname) ? parsed : null;
  } catch {
    return null;
  }
}

export function getUnreadNotificationsCount(notifications) {
  return notifications.filter((notification) => !notification?.is_read).length;
}
