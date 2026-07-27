"use client";

import { useState } from "react";
import DatePickerSmart from "@/components/forms/DatePickerSmart";
import Button from "@/components/ui/Button";
import SkeletonPage from "@/components/ui/Skeleton";
import SubscriptionStatusBadge from "./SubscriptionStatusBadge";
import { formatDate } from "@/lib/utils";
import { formatSubscriptionMoney, parseSubscriptionAmount } from "./subscriptionUtils";

/**
 * Renders a labeled value inside the subscription detail grid.
 */
function DetailItem({ label, value, tone = "default" }) {
  const toneClass =
    tone === "green"
      ? "text-app-green"
      : tone === "red"
        ? "text-app-red"
        : tone === "yellow"
          ? "text-app-yellow"
          : "text-app-text";

  return (
    <div className="rounded-lg border border-app-line bg-app-card-soft/70 p-3 text-right">
      <p className="text-[11px] text-app-muted-light">{label}</p>
      <p className={`mt-1 truncate text-sm font-medium ${toneClass}`}>{value || "-"}</p>
    </div>
  );
}

/**
 * Groups related subscription detail fields under one heading.
 */
function DetailSection({ title, children }) {
  return (
    <section className="space-y-3">
      <h3 className="text-sm font-medium text-app-yellow">{title}</h3>
      <div className="grid gap-3 sm:grid-cols-2">{children}</div>
    </section>
  );
}

/**
 * Renders subscription details and the actions allowed for its current status.
 */
export default function SubscriptionDetails({
  subscription,
  error,
  isLoading,
  onRetry,
  onFreeze,
  onUnfreeze,
  onCancel,
  isFreezing,
  isUnfreezing,
  isCancelling,
}) {
  const [showFreezeForm, setShowFreezeForm] = useState(false);
  const [daysCount, setDaysCount] = useState("7");
  const [startDate, setStartDate] = useState(new Date().toISOString().split("T")[0]);

  if (isLoading) {
    return <SkeletonPage blocks={[{ type: "details", sections: 4, itemsPerSection: 4 }]} />;
  }

  if (error) {
    return (
      <div className="space-y-3 rounded-xl border border-app-red/30 bg-app-red/10 p-5 text-right text-sm text-app-red">
        <p>تعذر تحميل تفاصيل الاشتراك المحدد.</p>
        <Button tone="outline" className="h-9 px-3 text-xs" onClick={onRetry}>
          إعادة المحاولة
        </Button>
      </div>
    );
  }

  if (!subscription) {
    return (
      <div className="rounded-xl border border-app-line bg-app-card-soft/60 p-6 text-center text-sm text-app-muted-light">
        لا توجد تفاصيل لهذا الاشتراك.
      </div>
    );
  }

  const member = subscription.member || {};
  const person = member.person || {};
  const plan = subscription.plan || {};
  const planName = plan.name?.ar || plan.name?.en || "-";

  return (
    <div className="space-y-6">
      <div className="rounded-xl border border-app-line bg-app-card-soft/70 p-4 text-right">
        <div className="flex items-start justify-between gap-3">
          <div className="min-w-0">
            <h3 className="truncate text-lg font-medium text-app-text">
              {person.full_name || "-"}
            </h3>
            <p className="mt-1 text-xs text-app-muted-light" dir="ltr">
              {member.member_number || "-"}
            </p>
          </div>
          <SubscriptionStatusBadge status={subscription.status} />
        </div>
      </div>

      <DetailSection title="بيانات العضو">
        <DetailItem label="الاسم" value={person.full_name} />
        <DetailItem label="رقم العضوية" value={member.member_number} />
        <DetailItem label="البريد الإلكتروني" value={person.email} />
        <DetailItem label="الهاتف" value={person.phone} />
      </DetailSection>

      <DetailSection title="الخطة والمدة">
        <DetailItem label="الخطة" value={planName} />
        <DetailItem label="نوع الخطة" value={plan.type} />
        <DetailItem label="تاريخ البداية" value={formatDate(subscription.start_date)} />
        <DetailItem label="تاريخ النهاية" value={formatDate(subscription.end_date)} />
        <DetailItem label="عدد الجلسات" value={plan.session_count} />
        <DetailItem
          label="الجلسات المتبقية"
          value={subscription.remaining_sessions}
          tone="yellow"
        />
      </DetailSection>

      <DetailSection title="المدفوعات">
        <DetailItem
          label="إجمالي الاشتراك"
          value={formatSubscriptionMoney(subscription.total_amount)}
          tone="yellow"
        />
        <DetailItem
          label="المدفوع"
          value={formatSubscriptionMoney(subscription.paid_amount)}
          tone="green"
        />
        <DetailItem
          label="المتبقي"
          value={formatSubscriptionMoney(subscription.remaining_amount)}
          tone={parseSubscriptionAmount(subscription.remaining_amount) > 0 ? "red" : "green"}
        />
        <DetailItem label="المدرب المسؤول" value={subscription.coach?.person?.full_name || "-"} />
      </DetailSection>

      {/* يعرض سجل الفترات التي جُمّد فيها الاشتراك. */}
      {subscription.freezes && subscription.freezes.length > 0 && (
        <div className="rounded-xl border border-app-line bg-app-card-soft/40 p-4 text-right space-y-2">
          <h4 className="text-xs font-semibold text-white">سجل تجميد الاشتراك</h4>
          <div className="space-y-2">
            {subscription.freezes.map((f, i) => (
              <div
                key={i}
                className="flex items-center justify-between p-2 rounded bg-black/25 text-xs text-app-text border border-app-line"
              >
                <span className="text-app-muted-light">
                  {formatDate(f.start_date)} ← {formatDate(f.end_date)}
                </span>
                <span className="font-semibold text-app-yellow">{f.days_count} يوم تجميد</span>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* يقيّد الإجراءات بحسب حالة الاشتراك الحالية. */}
      <div className="border-t border-app-line pt-4 space-y-3">
        {subscription.status === "active" && (
          <>
            {!showFreezeForm ? (
              <div className="flex gap-3">
                <Button
                  type="button"
                  tone="warning"
                  className="flex-1 h-10 text-sm font-semibold"
                  onClick={() => setShowFreezeForm(true)}
                >
                  تجميد الاشتراك
                </Button>
                <Button
                  type="button"
                  tone="danger"
                  className="flex-1 h-10 text-sm font-semibold"
                  onClick={() => onCancel(subscription.id)}
                  loading={isCancelling}
                >
                  إلغاء الاشتراك
                </Button>
              </div>
            ) : (
              <div className="rounded-xl border border-app-line bg-app-card-soft p-4 space-y-3 text-right">
                <h4 className="text-xs font-bold text-white">تجميد الاشتراك الحالي</h4>
                <div className="grid grid-cols-2 gap-3">
                  <DatePickerSmart
                    label="تاريخ البدء"
                    value={startDate}
                    onChange={setStartDate}
                    compact={true}
                  />
                  <label className="block text-xs text-app-muted-light">
                    عدد الأيام
                    <input
                      type="number"
                      min="1"
                      value={daysCount}
                      onChange={(e) => setDaysCount(e.target.value)}
                      className="app-input mt-1.5 h-9 w-full px-2 text-right bg-black/35 text-white"
                    />
                  </label>
                </div>
                <div className="flex gap-2 pt-1">
                  <Button
                    type="button"
                    tone="outline"
                    className="h-9 px-3 text-xs flex-1"
                    onClick={() => setShowFreezeForm(false)}
                  >
                    تراجع
                  </Button>
                  <Button
                    type="button"
                    tone="warning"
                    className="h-9 px-3 text-xs flex-1"
                    loading={isFreezing}
                    onClick={() => {
                      onFreeze(subscription.id, {
                        start_date: startDate,
                        days_count: Number(daysCount) || 7,
                      });
                      setShowFreezeForm(false);
                    }}
                  >
                    تأكيد التجميد
                  </Button>
                </div>
              </div>
            )}
          </>
        )}

        {subscription.status === "frozen" && (
          <Button
            type="button"
            className="w-full h-10 text-sm font-semibold"
            loading={isUnfreezing}
            onClick={() => onUnfreeze(subscription.id)}
          >
            إلغاء التجميد وتفعيل الاشتراك
          </Button>
        )}
      </div>
    </div>
  );
}
