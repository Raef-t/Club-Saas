"use client";

import Button from "@/components/ui/Button";
import Checkbox from "@/components/ui/Checkbox";
import Dropdown from "@/components/ui/Dropdown";
import SkeletonPage from "@/components/ui/Skeleton";
import { CheckCircleIcon } from "./AttendanceIcons";
import { getAttendancePhotoUrl } from "./attendanceUtils";

/**
 * Renders one labeled value in the member summary.
 */
function InfoRow({ label, value, tone = "default" }) {
  const valueClass =
    tone === "yellow" ? "text-app-yellow" : tone === "green" ? "text-app-green" : "text-app-text";
  const displayValue = value === null || value === undefined || value === "" ? "-" : value;

  return (
    <div className="flex items-center justify-between gap-4 text-sm">
      <span className={`min-w-0 truncate font-medium ${valueClass}`}>{displayValue}</span>
      <span className="shrink-0 text-app-muted-light">{label}</span>
    </div>
  );
}

/**
 * Displays an empty state before the first member QR code is scanned.
 */
function EmptyMemberCard() {
  return (
    <section
      className="app-card flex min-h-[300px] w-full flex-col items-center justify-center rounded-2xl border border-dashed border-app-line p-6 text-center"
      dir="rtl"
    >
      <div className="flex size-16 items-center justify-center rounded-full bg-app-panel-soft text-app-muted-light">
        <svg
          className="size-8 animate-pulse text-app-muted"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          strokeWidth="1.5"
          aria-hidden="true"
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"
          />
        </svg>
      </div>
      <h3 className="mt-4 text-base font-medium text-app-text">بانتظار مسح بطاقة اللاعب</h3>
      <p className="mt-2 max-w-[260px] text-xs leading-relaxed text-app-muted">
        استخدم قارئ QR لمسح بطاقة العضو وعرض الاشتراكات المتاحة قبل تسجيل الحضور.
      </p>
    </section>
  );
}

/**
 * Renders member information and the subscription session deduction controls.
 */
export default function AttendancePlayerCard({
  member,
  selectedSubscription,
  selectedActivity,
  playerSubscriptions,
  activityOptions,
  selectedSubscriptionIds,
  selectedActivityId,
  lockerNumber,
  isMemberLoading,
  memberErrorMessage,
  isSubscriptionsLoading,
  subscriptionsErrorMessage,
  isRegistered,
  isPendingDeduction,
  isRegistering,
  onRetryMember,
  onRetrySubscriptions,
  onSubscriptionToggle,
  onActivityChange,
  onLockerChange,
  onRegister,
}) {
  if (isMemberLoading) {
    return (
      <section className="app-card min-h-[300px] w-full rounded-2xl p-6">
        <SkeletonPage blocks={[{ type: "details", sections: 2, itemsPerSection: 3 }]} />
      </section>
    );
  }

  if (memberErrorMessage) {
    return (
      <section
        className="app-card flex min-h-[300px] w-full flex-col items-center justify-center rounded-2xl border border-app-red/30 p-6 text-center"
        dir="rtl"
      >
        <p className="text-sm text-app-red">{memberErrorMessage}</p>
        <Button
          type="button"
          tone="outline"
          className="mt-4 h-9 px-3 text-xs"
          onClick={onRetryMember}
        >
          إعادة المحاولة
        </Button>
      </section>
    );
  }

  if (!member) {
    return <EmptyMemberCard />;
  }

  const photoSrc = getAttendancePhotoUrl(member.photoUrl);
  const hasSubscriptions = playerSubscriptions.length > 0;
  const registerDisabled =
    !hasSubscriptions ||
    !selectedSubscriptionIds.length ||
    !isPendingDeduction ||
    isRegistered ||
    isRegistering ||
    Boolean(subscriptionsErrorMessage);

  return (
    <section className="app-card w-full rounded-2xl p-6" dir="rtl">
      <div className="flex items-center justify-center gap-2 text-app-green">
        <span className="text-lg font-medium">
          {isRegistered ? "تم تأكيد الحضور" : "بيانات اللاعب"}
        </span>
        <CheckCircleIcon className="size-5" />
      </div>

      <div className="mt-4 flex flex-col items-center">
        {photoSrc ? (
          <img
            src={photoSrc}
            alt={member.name}
            className="size-24 rounded-full border border-app-line object-cover"
          />
        ) : (
          <div className="grid size-24 place-items-center rounded-full border border-app-line bg-app-card-soft text-3xl font-medium text-app-yellow">
            {member.avatar}
          </div>
        )}
        <h2 className="mt-3 text-xl font-medium text-app-text">{member.name}</h2>
        <p className="mt-1 text-sm text-app-muted-light" dir="ltr">
          {member.number}
        </p>
      </div>

      <div className="mt-6 space-y-4">
        <InfoRow label="النشاط" value={selectedActivity?.label || selectedSubscription?.activity} />
        <InfoRow label="المدرب" value={selectedActivity?.coach || selectedSubscription?.coach} />
        <InfoRow label="الحصص المتبقية" value={selectedSubscription?.remaining} tone="yellow" />
        <InfoRow label="ينتهي" value={selectedSubscription?.endsAt} />
      </div>

      <div className="mt-6 space-y-4 border-t border-app-line pt-5">
        <div className="block text-right text-sm text-app-muted-light">
          اشتراكات اللاعب
          {isSubscriptionsLoading ? (
            <p className="mt-2 text-xs text-app-yellow">جاري تحميل الاشتراكات...</p>
          ) : subscriptionsErrorMessage ? (
            <div className="mt-2 rounded-lg border border-app-red/30 bg-app-red/10 p-3">
              <p className="text-xs text-app-red">{subscriptionsErrorMessage}</p>
              <Button
                type="button"
                tone="outline"
                className="mt-3 h-8 px-3 text-xs"
                onClick={onRetrySubscriptions}
              >
                إعادة المحاولة
              </Button>
            </div>
          ) : hasSubscriptions ? (
            <div className="mt-2 space-y-2">
              {playerSubscriptions.map((subscription) => (
                <Checkbox
                  key={subscription.id}
                  label={`${subscription.label} - ${subscription.remaining} حصة`}
                  checked={selectedSubscriptionIds.includes(String(subscription.id))}
                  onChange={() => onSubscriptionToggle(subscription.id)}
                  disabled={isRegistering}
                />
              ))}
            </div>
          ) : (
            <p className="mt-2 text-xs text-app-red">لا توجد اشتراكات متاحة لهذا العضو.</p>
          )}
        </div>

        <label className="block text-right text-sm text-app-muted-light">
          النشاط اليوم
          <Dropdown
            className="mt-2 text-white"
            buttonClassName="h-11 bg-app-card-soft"
            value={selectedActivityId}
            onChange={onActivityChange}
            options={activityOptions}
            placeholder="اختر النشاط"
            disabled={!activityOptions.length || isRegistering}
          />
        </label>

        <label className="block text-right text-sm text-app-muted-light">
          رقم الخزانة
          <input
            value={lockerNumber}
            onChange={(event) => onLockerChange(event.target.value)}
            className="app-input mt-2 h-11 w-full px-3 text-right outline-none transition focus:border-app-yellow/70 disabled:cursor-not-allowed disabled:opacity-60"
            placeholder="مثال: 18"
            inputMode="numeric"
            disabled={isRegistering}
          />
        </label>

        <Button
          type="button"
          className="h-11 w-full"
          onClick={onRegister}
          disabled={registerDisabled}
          loading={isRegistering}
        >
          {isRegistered
            ? "تم تأكيد الحضور"
            : isPendingDeduction
              ? "خصم الجلسة وتأكيد الحضور"
              : "لا توجد حركة دخول معلّقة"}
        </Button>
      </div>
    </section>
  );
}
