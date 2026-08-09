import { getAttendanceCollection } from "../attendance/attendanceUtils";
import { getLockerCollection } from "../lockers/lockerUtils";
import {
  getCurrentMemberSubscription,
  getSubscriptionRows,
  parseSubscriptionAmount,
} from "../subscriptions/subscriptionUtils";

function getMemberId(record) {
  return (
    record?.member_id ??
    record?.player_id ??
    record?.attendable_id ??
    record?.member?.id ??
    record?.player?.id ??
    null
  );
}

export function getMemberProfileRecord(response) {
  const payload = response?.data?.data || response?.data || response;
  const record = payload?.member || payload;
  return record && typeof record === "object" && !Array.isArray(record) ? record : null;
}

function belongsToMember(record, memberId) {
  const recordMemberId = getMemberId(record);
  return recordMemberId == null || String(recordMemberId) === String(memberId);
}

function getTimestamp(record) {
  const value =
    record?.check_in_at ||
    record?.check_in ||
    record?.start_date ||
    record?.created_at ||
    record?.id ||
    0;
  const timestamp = new Date(value).getTime();
  return Number.isFinite(timestamp) ? timestamp : Number(record?.id) || 0;
}

export function getMemberProfileSubscriptions(response, memberId) {
  return getSubscriptionRows(response)
    .filter((subscription) => belongsToMember(subscription, memberId))
    .sort((first, second) => getTimestamp(second) - getTimestamp(first));
}

export function getMemberProfileAttendances(response, memberId) {
  return getAttendanceCollection(response)
    .filter(
      (attendance) =>
        attendance?.attendable_type !== "staff" && belongsToMember(attendance, memberId),
    )
    .sort((first, second) => getTimestamp(second) - getTimestamp(first));
}

export function getMemberProfileLockers(response, memberId) {
  return getLockerCollection(response).filter(
    (locker) => locker?.holder_type === "member" && String(locker?.holder_id) === String(memberId),
  );
}

export function createMemberProfileSummary({ subscriptions, attendances, lockers, memberId }) {
  const currentSubscription = getCurrentMemberSubscription({ data: subscriptions }, memberId);
  const paidAmount = subscriptions.reduce(
    (sum, subscription) => sum + parseSubscriptionAmount(subscription.paid_amount),
    0,
  );
  const remainingAmount = subscriptions.reduce(
    (sum, subscription) => sum + parseSubscriptionAmount(subscription.remaining_amount),
    0,
  );

  return {
    currentSubscription,
    subscriptionsCount: subscriptions.length,
    attendanceCount: attendances.length,
    paidAmount,
    remainingAmount,
    lockerCount: lockers.length,
    lastAttendance: attendances[0] || null,
  };
}
