import DetailItem from "@/components/ui/DetailItem";
import Button from "@/components/ui/Button";
import ProfileIdentityCard from "@/components/ui/ProfileIdentityCard";
import SubscriptionStatusBadge from "@/app/management/subscriptions/SubscriptionStatusBadge";
import { CalendarIcon, ClockIcon, LockerIcon, TagIcon } from "@/components/icons/Icons";
import { formatDate, formatLocalizedName } from "@/lib/utils";
import {
  formatSubscriptionMoney,
  parseSubscriptionAmount,
} from "@/app/management/subscriptions/subscriptionUtils";
import {
  formatAttendanceTime,
  getAttendanceStatusLabel,
} from "@/app/management/attendance/attendanceUtils";
import {
  MEMBER_GENDER_LABELS as genderLabels,
  RELATION_LABELS as relationLabels,
} from "./memberConstants";

const MEMBER_STATUS_META = {
  active: { label: "نشط", className: "bg-app-green/10 text-app-green" },
  inactive: { label: "غير نشط", className: "bg-app-red/10 text-app-red" },
  suspended: { label: "موقوف", className: "bg-app-red/10 text-app-red" },
  expired: { label: "منتهي", className: "bg-app-red/10 text-app-red" },
  pending: { label: "قيد الانتظار", className: "bg-app-yellow/10 text-app-yellow" },
};

function Section({ title, description, action, children }) {
  return (
    <section className="rounded-2xl border border-app-line bg-app-card-soft/45 p-4 sm:p-5">
      <div className="mb-4 flex items-start justify-between gap-4">
        <div>
          <h3 className="text-sm font-semibold text-app-text">{title}</h3>
          {description && <p className="mt-1 text-xs text-app-muted-light">{description}</p>}
        </div>
        {action}
      </div>
      {children}
    </section>
  );
}

function MetricCard({ icon, label, value, helper, tone = "default" }) {
  const toneClass =
    tone === "green"
      ? "text-app-green"
      : tone === "red"
        ? "text-app-red"
        : tone === "yellow"
          ? "text-app-yellow"
          : "text-app-text";

  return (
    <div className="rounded-xl border border-app-line bg-black/20 p-3.5">
      <div className="flex items-center gap-2 text-xs text-app-muted-light">
        <span className="text-app-yellow">{icon}</span>
        <span>{label}</span>
      </div>
      <p className={`mt-2 truncate text-lg font-semibold ${toneClass}`}>{value}</p>
      {helper && <p className="mt-1 truncate text-[11px] text-app-muted">{helper}</p>}
    </div>
  );
}

function SectionState({ isLoading, error, emptyMessage, onRetry, children }) {
  if (isLoading) {
    return (
      <div className="space-y-2" role="status" aria-label="جاري تحميل بيانات الملف">
        {[0, 1, 2].map((item) => (
          <div key={item} className="h-14 animate-pulse rounded-xl bg-app-line-soft/70" />
        ))}
      </div>
    );
  }

  if (error) {
    return (
      <div className="flex flex-col items-center gap-3 rounded-xl border border-app-red/30 bg-app-red/10 p-4 text-center text-xs text-app-red">
        <p>{error}</p>
        {onRetry && (
          <Button type="button" tone="outline" className="h-8 px-3 text-xs" onClick={onRetry}>
            إعادة المحاولة
          </Button>
        )}
      </div>
    );
  }

  if (!children) {
    return (
      <div className="rounded-xl border border-dashed border-app-line p-4 text-center text-xs text-app-muted-light">
        {emptyMessage}
      </div>
    );
  }

  return children;
}

function getPlanName(subscription) {
  return formatLocalizedName(subscription?.plan?.name || subscription?.plan_name);
}

function getAttendanceCheckIn(attendance) {
  return attendance?.check_in_at || attendance?.check_in || attendance?.created_at;
}

function CurrentSubscription({ subscription, isLoading, error, onRetry, onShowSubscription }) {
  const items = subscription?.items || [];
  const totalAllocated = items.reduce((sum, item) => sum + Number(item.sessions_allocated || 0), 0);
  const totalConsumed = items.reduce((sum, item) => sum + Number(item.sessions_consumed || 0), 0);
  const activities = [
    ...new Set(
      items
        .map((item) => formatLocalizedName(item.activity?.name || item.activity_name))
        .filter((name) => name && name !== "-"),
    ),
  ];

  return (
    <Section
      title="الاشتراك الحالي"
      description="الخطة الفعالة والجلسات والوضع المالي"
      action={subscription ? <SubscriptionStatusBadge status={subscription.status} /> : null}
    >
      <SectionState
        isLoading={isLoading}
        error={error ? "تعذر تحميل بيانات الاشتراك." : ""}
        emptyMessage="لا يوجد اشتراك مسجل لهذا اللاعب."
        onRetry={onRetry}
      >
        {subscription ? (
          <div className="space-y-4">
            <div className="flex flex-col gap-3 rounded-xl border border-app-line bg-black/20 p-4 sm:flex-row sm:items-center sm:justify-between">
              <div className="min-w-0">
                <p className="truncate text-base font-semibold text-app-text">
                  {getPlanName(subscription)}
                </p>
                <p className="mt-1 text-xs text-app-muted-light">
                  {activities.length ? activities.join("، ") : "لم تُحدد الأنشطة"}
                </p>
              </div>
              <div className="text-right sm:text-left">
                <p className="text-xs text-app-muted-light">المتبقي</p>
                <p
                  className={`mt-1 font-semibold ${
                    parseSubscriptionAmount(subscription.remaining_amount) > 0
                      ? "text-app-red"
                      : "text-app-green"
                  }`}
                >
                  {formatSubscriptionMoney(subscription.remaining_amount)}
                </p>
              </div>
            </div>

            <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
              <DetailItem label="تاريخ البداية" value={formatDate(subscription.start_date)} />
              <DetailItem label="تاريخ النهاية" value={formatDate(subscription.end_date)} />
              <DetailItem
                label="الجلسات المتبقية"
                value={
                  totalAllocated > 0
                    ? `${Math.max(0, totalAllocated - totalConsumed)} / ${totalAllocated}`
                    : "-"
                }
                tone="yellow"
              />
              <DetailItem
                label="المدفوع"
                value={formatSubscriptionMoney(subscription.paid_amount)}
                tone="green"
              />
            </div>

            <Button
              type="button"
              tone="outline"
              className="h-10 w-full"
              onClick={onShowSubscription}
            >
              عرض كل تفاصيل الاشتراك
            </Button>
          </div>
        ) : null}
      </SectionState>
    </Section>
  );
}

function AttendanceHistory({ attendances, isLoading, error, onRetry }) {
  const recentAttendances = attendances.slice(0, 5);

  return (
    <Section title="آخر الحضور" description="آخر خمس حركات مسجلة للاعب">
      <SectionState
        isLoading={isLoading}
        error={error ? "تعذر تحميل سجل الحضور." : ""}
        emptyMessage="لا توجد حركات حضور مسجلة حتى الآن."
        onRetry={onRetry}
      >
        {recentAttendances.length ? (
          <div className="divide-y divide-app-line overflow-hidden rounded-xl border border-app-line bg-black/20">
            {recentAttendances.map((attendance, index) => {
              const checkIn = getAttendanceCheckIn(attendance);
              const checkOut = attendance.check_out_at || attendance.check_out;
              const isOpen = attendance.status === "checked_in" && !checkOut;
              const activityName = formatLocalizedName(
                attendance.activity?.name || attendance.activity_name,
              );

              return (
                <div
                  key={attendance.id || `${checkIn}-${index}`}
                  className="grid gap-3 px-4 py-3 sm:grid-cols-[1fr_auto_auto] sm:items-center"
                >
                  <div>
                    <p className="text-sm font-medium text-app-text">{formatDate(checkIn)}</p>
                    <p className="mt-1 text-[11px] text-app-muted-light">
                      {activityName === "-" ? "حضور النادي" : activityName}
                    </p>
                  </div>
                  <p className="text-xs text-app-muted-light" dir="ltr">
                    {formatAttendanceTime(checkIn)} — {formatAttendanceTime(checkOut)}
                  </p>
                  <span
                    className={`w-fit rounded-full px-2.5 py-1 text-[10px] font-semibold ${
                      isOpen ? "bg-app-green/10 text-app-green" : "bg-white/5 text-app-muted-light"
                    }`}
                  >
                    {isOpen ? "داخل النادي" : getAttendanceStatusLabel(attendance.status)}
                  </span>
                </div>
              );
            })}
          </div>
        ) : null}
      </SectionState>
    </Section>
  );
}

function SubscriptionHistory({ subscriptions, isLoading, error, onRetry }) {
  return (
    <Section title="سجل الاشتراكات" description="جميع الاشتراكات المرتبطة باللاعب">
      <SectionState
        isLoading={isLoading}
        error={error ? "تعذر تحميل سجل الاشتراكات." : ""}
        emptyMessage="لا يوجد سجل اشتراكات لهذا اللاعب."
        onRetry={onRetry}
      >
        {subscriptions.length ? (
          <div className="grid gap-3 md:grid-cols-2">
            {subscriptions.map((subscription) => (
              <div
                key={subscription.id}
                className="rounded-xl border border-app-line bg-black/20 p-4"
              >
                <div className="flex items-start justify-between gap-3">
                  <div className="min-w-0">
                    <p className="truncate text-sm font-semibold text-app-text">
                      {getPlanName(subscription)}
                    </p>
                    <p className="mt-1 text-[11px] text-app-muted-light">
                      {formatDate(subscription.start_date)} — {formatDate(subscription.end_date)}
                    </p>
                  </div>
                  <SubscriptionStatusBadge status={subscription.status} />
                </div>
                <div className="mt-3 flex items-center justify-between border-t border-app-line pt-3 text-xs">
                  <span className="text-app-muted-light">المتبقي</span>
                  <span
                    className={
                      parseSubscriptionAmount(subscription.remaining_amount) > 0
                        ? "font-semibold text-app-red"
                        : "font-semibold text-app-green"
                    }
                  >
                    {formatSubscriptionMoney(subscription.remaining_amount)}
                  </span>
                </div>
              </div>
            ))}
          </div>
        ) : null}
      </SectionState>
    </Section>
  );
}

export default function MemberDetails({
  member,
  branches = [],
  subscription,
  subscriptions = [],
  attendances = [],
  lockers = [],
  summary,
  onShowSubscription,
  currentSubscriptionLoading = false,
  subscriptionsLoading = false,
  attendancesLoading = false,
  lockersLoading = false,
  subscriptionsError,
  currentSubscriptionError,
  attendancesError,
  lockersError,
  onRetrySubscriptions,
  onRetryAttendances,
  onRetryLockers,
}) {
  if (!member) {
    return (
      <div className="rounded-xl border border-app-line bg-app-card-soft/60 p-6 text-center text-sm text-app-muted-light">
        لا توجد تفاصيل لهذا العضو.
      </div>
    );
  }

  const person = member.person || {};
  const branch = branches.find((item) => String(item.id) === String(member.branch_id));
  const branchName = branch ? formatLocalizedName(branch.name) : `فرع #${member.branch_id || "-"}`;
  const personalContact = person.contacts?.find(
    (contact) => contact.relation === "self" || contact.name === "Personal",
  );
  const emergencyContact = person.contacts?.find((contact) => contact.relation !== "self");
  const fullName =
    person.full_name || `${member.first_name || ""} ${member.last_name || ""}`.trim() || "-";
  const gender = person.gender || member.gender || "-";
  const mobile =
    personalContact?.phone_number || person.phone || person.mobile || member.mobile || "";
  const countryCode =
    personalContact?.country_code || person.mobile_country_code || member.mobile_country_code || "";
  const dob = person.dob || member.dob || "";
  const age = person.age ?? member.age;
  const membershipStatus =
    member.membership_status || (member.is_active === false ? "inactive" : "active");
  const status = MEMBER_STATUS_META[membershipStatus] || {
    label: membershipStatus,
    className: "bg-app-card-hover text-app-muted-light",
  };
  const lastAttendanceDate = getAttendanceCheckIn(summary?.lastAttendance);

  return (
    <div className="space-y-5">
      <ProfileIdentityCard
        name={fullName}
        qrCode={member.today_qr_code || member.qr_code}
        status={status}
      />

      <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <MetricCard
          icon={<TagIcon className="size-4" />}
          label="الاشتراك الحالي"
          value={subscription ? getPlanName(subscription) : "لا يوجد"}
          helper={`${summary?.subscriptionsCount || 0} اشتراك في السجل`}
          tone={subscription ? "green" : "default"}
        />
        <MetricCard
          icon={<ClockIcon className="size-4" />}
          label="إجمالي الزيارات"
          value={(summary?.attendanceCount || 0).toLocaleString("ar")}
          helper={
            lastAttendanceDate ? `آخر حضور ${formatDate(lastAttendanceDate)}` : "لا يوجد حضور"
          }
        />
        <MetricCard
          icon={<CalendarIcon className="size-4" />}
          label="المبلغ المتبقي"
          value={formatSubscriptionMoney(summary?.remainingAmount || 0)}
          helper={`مدفوع ${formatSubscriptionMoney(summary?.paidAmount || 0)}`}
          tone={(summary?.remainingAmount || 0) > 0 ? "red" : "green"}
        />
        <MetricCard
          icon={<LockerIcon className="size-4" />}
          label="الخزائن الحالية"
          value={(summary?.lockerCount || 0).toLocaleString("ar")}
          helper={
            lockers.length
              ? lockers.map((locker) => locker.locker_number).join("، ")
              : "لا توجد خزانة"
          }
          tone={lockers.length ? "yellow" : "default"}
        />
      </div>

      <div className="grid items-start gap-5 xl:grid-cols-[minmax(0,1.7fr)_minmax(280px,1fr)]">
        <div className="space-y-5">
          <CurrentSubscription
            subscription={subscription}
            isLoading={currentSubscriptionLoading}
            error={currentSubscriptionError}
            onRetry={onRetrySubscriptions}
            onShowSubscription={onShowSubscription}
          />
          <AttendanceHistory
            attendances={attendances}
            isLoading={attendancesLoading}
            error={attendancesError}
            onRetry={onRetryAttendances}
          />
        </div>

        <div className="space-y-5">
          <Section title="بيانات اللاعب">
            <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
              <DetailItem label="الفرع" value={branchName} tone="yellow" />
              <DetailItem label="الجنس" value={genderLabels[gender] || gender} />
              <DetailItem label="الهاتف" value={mobile ? `${countryCode} ${mobile}`.trim() : "-"} />
              <DetailItem label="تاريخ الميلاد" value={formatDate(dob)} />
              <DetailItem label="العمر" value={age !== "" && age != null ? `${age} سنة` : "-"} />
              <DetailItem label="تاريخ تسجيل الاستمارة" value={formatDate(member.created_at)} />
              <DetailItem label="الموظف المسجل" value={member.registered_by?.full_name || "اسم الموظف"} />
            </div>
          </Section>

          <Section title="الخزائن" description="الخزائن المسندة حاليًا">
            <SectionState
              isLoading={lockersLoading}
              error={lockersError ? "تعذر تحميل بيانات الخزائن." : ""}
              emptyMessage="لا توجد خزانة مسندة لهذا اللاعب."
              onRetry={onRetryLockers}
            >
              {lockers.length ? (
                <div className="space-y-2">
                  {lockers.map((locker) => (
                    <div
                      key={locker.id}
                      className="flex items-center justify-between rounded-xl border border-app-line bg-black/20 px-4 py-3"
                    >
                      <span className="text-sm text-app-text">خزانة {locker.locker_number}</span>
                      <span className="text-xs text-app-yellow">
                        {locker.key_number || "مسندة"}
                      </span>
                    </div>
                  ))}
                </div>
              ) : null}
            </SectionState>
          </Section>

          {emergencyContact && (
            <Section title="جهة اتصال الطوارئ">
              <div className="space-y-3">
                <DetailItem label="الاسم" value={emergencyContact.name} />
                <DetailItem
                  label="العلاقة"
                  value={relationLabels[emergencyContact.relation] || emergencyContact.relation}
                />
                <DetailItem
                  label="رقم الهاتف"
                  value={
                    emergencyContact.phone_number
                      ? `${emergencyContact.country_code || ""} ${emergencyContact.phone_number}`
                      : "-"
                  }
                />
              </div>
            </Section>
          )}
        </div>
      </div>

      <SubscriptionHistory
        subscriptions={subscriptions}
        isLoading={subscriptionsLoading}
        error={subscriptionsError}
        onRetry={onRetrySubscriptions}
      />
    </div>
  );
}
