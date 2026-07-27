import { formatDate, formatLocalizedName, getBranchesArray } from "../../../lib/utils";
import { ATTENDANCE_STATUS_LABELS } from "./attendanceConstants";

const BACKEND_ASSET_ORIGIN = process.env.NEXT_PUBLIC_BACKEND_ASSET_URL || "http://31.70.108.63";

/**
 * Extracts an array from the supported backend collection response shapes.
 */
export function getAttendanceCollection(response) {
  if (Array.isArray(response?.data?.data)) return response.data.data;
  if (Array.isArray(response?.data)) return response.data;
  if (Array.isArray(response)) return response;
  return [];
}

/**
 * Converts a member response into the compact model required by the attendance UI.
 */
export function createAttendanceMember(response, memberId) {
  if (!memberId) return null;

  const member = response?.data?.data || response?.data || {};
  const person = member.person || {};
  const fullName = person.full_name || `عضو #${memberId}`;

  return {
    id: member.id || memberId,
    name: fullName,
    number: member.member_number || `M-${memberId}`,
    avatar: fullName.charAt(0) || "ع",
    photoUrl: person.photo_url || null,
  };
}

/**
 * Formats a check-in timestamp for the attendance table.
 */
export function formatAttendanceTime(value) {
  if (!value) return "-";

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return "-";

  return date
    .toLocaleTimeString("en-US", {
      hour: "2-digit",
      minute: "2-digit",
      hour12: true,
    })
    .toLowerCase();
}

/**
 * Resolves the localized label used for backend attendance statuses.
 */
export function getAttendanceStatusLabel(status) {
  return ATTENDANCE_STATUS_LABELS[status] || status || "غير معروف";
}

/**
 * Converts attendance history records into DataTable rows.
 */
export function createAttendanceRows(response, activeMember) {
  return getAttendanceCollection(response).map((record, index) => ({
    id: record.id || `attendance-${index}`,
    number: record.id ? `#${record.id}` : "-",
    time: formatAttendanceTime(record.check_in),
    member:
      activeMember?.name ||
      record.member?.person?.full_name ||
      record.member_name ||
      `عضو #${record.member_id || record.attendable_id || "-"}`,
    activity: formatLocalizedName(record.activity?.name || record.activity_name) || "-",
    coach: record.coach?.person?.full_name || record.coach?.name || record.coach_name || "-",
    locker:
      record.locker?.number || record.locker_number || record.active_locker?.locker_number || "-",
    duration: record.duration_minutes || null,
    status: getAttendanceStatusLabel(record.status),
  }));
}

/**
 * Converts member subscriptions into selectable attendance subscriptions.
 */
export function createAttendanceSubscriptions(response) {
  return getAttendanceCollection(response).map((subscription) => {
    const subscriptionId = subscription.player_subscription_id || subscription.id;
    const activities = (subscription.items || []).map((item) => ({
      id: String(item.activity_id || item.activity?.id || ""),
      label: formatLocalizedName(
        item.activity_name || item.activity?.name || subscription.plan_name,
      ),
      coach: item.coach?.person?.full_name || item.coach?.name || item.coach_name || "-",
    }));

    if (activities.length === 0) {
      activities.push({
        id: String(subscription.plan_id || subscriptionId || ""),
        label: formatLocalizedName(subscription.plan_name),
        coach: "-",
      });
    }

    return {
      id: String(subscriptionId || ""),
      memberId: subscription.member_id,
      label: formatLocalizedName(subscription.plan_name),
      activity: formatLocalizedName(subscription.plan_name),
      coach: activities[0]?.coach || "-",
      remaining: subscription.total_sessions_remaining ?? "-",
      endsAt: formatDate(subscription.end_date),
      activities,
      activeLockers: Array.isArray(subscription.active_lockers) ? subscription.active_lockers : [],
    };
  });
}

/**
 * Chooses safe defaults after scanning a member with one or more subscriptions.
 */
export function getInitialAttendanceSelection(subscriptions) {
  if (!subscriptions.length) {
    return {
      subscriptionIds: [],
      activityId: "",
      lockerNumber: "",
    };
  }

  const usableSubscriptions = subscriptions.filter(
    (subscription) => Number(subscription.remaining) > 0,
  );
  const selectedSubscriptions = usableSubscriptions.length
    ? usableSubscriptions
    : [subscriptions[0]];
  const firstSubscription = selectedSubscriptions[0];

  return {
    subscriptionIds: selectedSubscriptions.map((subscription) => String(subscription.id)),
    activityId: String(firstSubscription.activities?.[0]?.id || ""),
    lockerNumber: String(firstSubscription.activeLockers?.[0]?.locker_number || ""),
  };
}

/**
 * Toggles a subscription while ensuring at least one remains selected.
 */
export function toggleRequiredSubscription(selectedIds, subscriptionId) {
  const normalizedId = String(subscriptionId);
  const exists = selectedIds.includes(normalizedId);
  const nextIds = exists
    ? selectedIds.filter((id) => id !== normalizedId)
    : [...selectedIds, normalizedId];

  return nextIds.length ? nextIds : selectedIds;
}

/**
 * Normalizes server-loaded branches into dropdown options.
 */
export function createAttendanceBranchOptions(response) {
  return getBranchesArray(response).map((branch) => ({
    value: String(branch.id),
    label: formatLocalizedName(branch.name),
  }));
}

/**
 * Resolves an absolute member photo URL without changing existing absolute URLs.
 */
export function getAttendancePhotoUrl(photoUrl) {
  if (!photoUrl) return null;
  if (/^(https?:|blob:|data:)/i.test(photoUrl)) return photoUrl;

  return `${BACKEND_ASSET_ORIGIN}/${String(photoUrl).replace(/^\/+/, "")}`;
}
