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
 * Converts available lockers into searchable attendance dropdown options.
 * The client-side guard keeps occupied lockers out even if a backend ignores the status filter.
 */
export function createAvailableLockerOptions(response) {
  const lockers = getAttendanceLockerCollection(response);

  return lockers
    .filter((locker) => locker?.status === "available" && !locker?.holder_id)
    .map((locker) => ({
      value: String(locker.locker_number),
      label: `خزانة ${locker.locker_number}`,
    }))
    .sort((first, second) =>
      first.value.localeCompare(second.value, undefined, {
        numeric: true,
        sensitivity: "base",
      }),
    );
}

/**
 * Extracts locker records from the list endpoint response shapes.
 */
export function getAttendanceLockerCollection(response) {
  if (Array.isArray(response?.data?.lockers)) return response.data.lockers;
  if (Array.isArray(response?.data?.data?.lockers)) return response.data.data.lockers;
  if (Array.isArray(response?.data?.data)) return response.data.data;
  if (Array.isArray(response?.data)) return response.data;
  if (Array.isArray(response?.lockers)) return response.lockers;
  if (Array.isArray(response)) return response;
  return [];
}

/**
 * Resolves a locker id from its displayed locker number.
 */
export function findAttendanceLockerId(response, lockerNumber) {
  if (!lockerNumber) return null;
  const locker = getAttendanceLockerCollection(response).find(
    (item) => String(item?.locker_number) === String(lockerNumber),
  );

  return locker?.id ?? null;
}

/**
 * Adds locker details to attendance rows when history only returns the holder reference.
 */
export function attachAttendanceLockers(rows, lockersResponse) {
  const lockers = getAttendanceLockerCollection(lockersResponse);

  return rows.map((row) => {
    const locker = lockers.find((item) => {
      if (row.branchId && item.branch_id && String(item.branch_id) !== String(row.branchId)) {
        return false;
      }
      if (row.lockerId && String(item.id) === String(row.lockerId)) return true;
      if (row.lockerNumber && String(item.locker_number) === String(row.lockerNumber)) {
        return true;
      }

      return (
        item.holder_type === row.attendableType &&
        String(item.holder_id) === String(row.attendableId)
      );
    });

    if (!locker) return row;
    const lockerNumber = locker.locker_number || row.lockerNumber;

    return {
      ...row,
      lockerId: locker.id || row.lockerId,
      lockerNumber,
      locker: lockerNumber || "-",
    };
  });
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

  const normalizedValue =
    typeof value === "string" && /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(value)
      ? value.replace(" ", "T")
      : value;
  const date = new Date(normalizedValue);
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
 * Formats a check-in timestamp with both its calendar date and time.
 */
export function formatAttendanceDateTime(value) {
  if (!value) return "-";

  const normalizedValue =
    typeof value === "string" && /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(value)
      ? value.replace(" ", "T")
      : value;
  const date = new Date(normalizedValue);
  if (Number.isNaN(date.getTime())) return "-";

  const datePart = date.toLocaleDateString("en-GB", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  });

  return `${datePart} - ${formatAttendanceTime(value)}`;
}

/**
 * Combines an optional manual time with the local attendance date.
 * An empty or invalid time returns null so the backend can use server time.
 */
export function createManualCheckInTimestamp(time, date = new Date()) {
  if (!time || !(date instanceof Date) || Number.isNaN(date.getTime())) return null;

  const match = String(time).match(/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/);
  if (!match) return null;

  const hour = Number(match[1]);
  const minute = Number(match[2]);
  const second = Number(match[3] || 0);
  if (hour > 23 || minute > 59 || second > 59) return null;

  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const day = String(date.getDate()).padStart(2, "0");
  const timeValue = [hour, minute, second].map((part) => String(part).padStart(2, "0")).join(":");

  return `${year}-${month}-${day} ${timeValue}`;
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
export function createAttendanceRows(response, activeMember, people = {}) {
  const memberNames = createPersonNameMap(people.members, "member");
  const staffNames = createPersonNameMap(people.staff, "staff");

  return getAttendanceCollection(response).map((record, index) => {
    const locker = record.locker || record.active_locker || {};
    const lockerNumber = locker.locker_number || locker.number || record.locker_number || null;

    return {
      id: record.id || `attendance-${index}`,
      attendableId: record.attendable_id || record.member_id || record.staff_id || null,
      attendableType: record.attendable_type || (record.staff_id ? "staff" : "member"),
      branchId: record.branch_id || null,
      isOpen: record.status === "checked_in" && !(record.check_out_at || record.check_out),
      number: record.id ? `#${record.id}` : "-",
      type: record.attendable_type === "staff" ? "موظف" : "عضو",
      checkIn: formatAttendanceDateTime(record.check_in || record.check_in_at),
      checkOut: formatAttendanceTime(record.check_out_at || record.check_out),
      member:
        (activeMember &&
        record.attendable_type !== "staff" &&
        String(activeMember.id) === String(record.attendable_id)
          ? activeMember.name
          : null) ||
        record.member?.person?.full_name ||
        record.staff?.person?.full_name ||
        record.member_name ||
        record.staff_name ||
        (record.attendable_type === "staff"
          ? staffNames.get(String(record.attendable_id || record.staff_id))
          : memberNames.get(String(record.attendable_id || record.member_id))) ||
        `${record.attendable_type === "staff" ? "موظف" : "عضو"} #${record.attendable_id || record.member_id || record.staff_id || "-"}`,
      activity: formatLocalizedName(record.activity?.name || record.activity_name) || "-",
      coach: record.coach?.person?.full_name || record.coach?.name || record.coach_name || "-",
      lockerId: locker.id || record.locker_id || record.active_locker_id || null,
      lockerNumber,
      locker: lockerNumber || "-",
      duration:
        record.duration_formatted ||
        (record.duration_minutes !== null && record.duration_minutes !== undefined
          ? `${record.duration_minutes} دقيقة`
          : null),
      status: getAttendanceStatusLabel(record.status),
    };
  });
}

/**
 * Indexes members or staff records by the identifiers returned in attendance history.
 */
function createPersonNameMap(records, type) {
  const names = new Map();

  getAttendanceCollection(records).forEach((record) => {
    const name =
      record.person?.full_name ||
      record.full_name ||
      [record.person?.first_name, record.person?.last_name].filter(Boolean).join(" ");
    if (!name) return;

    const ids = type === "staff" ? [record.staff_id, record.id] : [record.member_id, record.id];
    ids
      .filter((id) => id !== null && id !== undefined)
      .forEach((id) => names.set(String(id), name));
  });

  return names;
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
      lockerNumber: "",
    };
  }

  const usableSubscriptions = subscriptions.filter(
    (subscription) => Number(subscription.remaining) > 0,
  );
  const selectedSubscriptions = usableSubscriptions.length
    ? usableSubscriptions
    : [subscriptions[0]];
  return {
    subscriptionIds: selectedSubscriptions.map((subscription) => String(subscription.id)),
    lockerNumber: "",
  };
}

/**
 * Builds the session-deduction body.
 */
export function createAttendanceDeductionBody(subscriptionIds) {
  return {
    player_subscription_ids: subscriptionIds.map(Number).filter(Number.isFinite),
  };
}

/**
 * Builds the free locker assignment used while confirming member attendance.
 */
export function createAttendanceLockerReservation(memberId, date = new Date()) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const day = String(date.getDate()).padStart(2, "0");

  return {
    reservation_type: "assign",
    holder_type: "member",
    holder_id: Number(memberId),
    start_date: `${year}-${month}-${day}`,
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
