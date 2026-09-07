"use client";

import { useState, useMemo } from "react";
import DatePickerSmart from "@/components/forms/DatePickerSmart";
import { formatDateDisplay } from "@/components/forms/datePickerUtils";
import Button from "@/components/ui/Button";
import SkeletonPage from "@/components/ui/Skeleton";
import SubscriptionStatusBadge from "./SubscriptionStatusBadge";
import SubscriptionReceiptBadges from "./SubscriptionReceiptBadges";
import { formatDate } from "@/lib/utils";
import {
  formatSubscriptionMoney,
  getSubscriptionCreatorName,
  parseSubscriptionAmount,
} from "./subscriptionUtils";

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
  showActions = true,
}) {
  const [showFreezeForm, setShowFreezeForm] = useState(false);
  const [freezeStartDate, setFreezeStartDate] = useState(new Date().toISOString().split("T")[0]);
  const [freezeDaysCount, setFreezeDaysCount] = useState("");
  const [freezeReason, setFreezeReason] = useState("");
  const [reasonError, setReasonError] = useState(false);
  const [startDateError, setStartDateError] = useState("");

  const calculatedFreezeEndDate = useMemo(() => {
    if (!freezeStartDate || !freezeDaysCount || Number(freezeDaysCount) <= 0) return "";
    const d = new Date(freezeStartDate);
    d.setDate(d.getDate() + Number(freezeDaysCount));
    return d.toISOString().split("T")[0];
  }, [freezeStartDate, freezeDaysCount]);

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
  const planName =
    typeof plan.name === "string" ? plan.name : plan.name?.ar || plan.name?.en || "-";
  const items = subscription.items || [];
  const totalAllocated = items.reduce((sum, item) => sum + (item.sessions_allocated || 0), 0);
  const totalConsumed = items.reduce((sum, item) => sum + (item.sessions_consumed || 0), 0);
  const remainingSessions = totalAllocated - totalConsumed;
  const coachNames =
    [...new Set(items.map((item) => item.coach?.name).filter(Boolean))].join("، ") || "-";
  const activityNames =
    [...new Set(items.map((item) => item.activity?.name).filter(Boolean))].join("، ") || "-";
  const revenueSplit = subscription.revenue_split;

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
        <DetailItem label="الهاتف" value={person.phone} />
      </DetailSection>

      <DetailSection title="الخطة والمدة">
        <DetailItem label="الخطة" value={planName} />
        <DetailItem
          label="العرض"
          value={subscription.offer?.name || subscription.offer_id || "بدون عرض"}
        />
        <DetailItem label="النشاط" value={activityNames} />
        <DetailItem label="تاريخ البداية" value={formatDate(subscription.start_date)} />
        <DetailItem label="تاريخ النهاية" value={formatDate(subscription.end_date)} />
        <DetailItem label="عدد الجلسات" value={plan.session_count} />
        <DetailItem
          label="الجلسات المتبقية"
          value={totalAllocated > 0 ? `${remainingSessions} / ${totalAllocated}` : "-"}
          tone="yellow"
        />
        <DetailItem label="عدد الأشهر" value={subscription.months_count} />
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
        <DetailItem label="المدرب المسؤول" value={coachNames} />
      </DetailSection>

      <section className="space-y-3">
        <h3 className="text-sm font-medium text-app-yellow">الإيصالات</h3>
        <div className="rounded-lg border border-app-line bg-app-card-soft/70 p-3">
          <SubscriptionReceiptBadges
            subscription={subscription}
            className="items-start sm:flex-row sm:flex-wrap"
          />
        </div>
      </section>

      {revenueSplit && (
        <DetailSection title="التوزيع المالي">
          <DetailItem
            label="إجمالي المبلغ"
            value={formatSubscriptionMoney(revenueSplit.total_amount)}
            tone="yellow"
          />
          <DetailItem
            label="حصة الكوتش"
            value={formatSubscriptionMoney(revenueSplit.coach_amount)}
            tone="green"
          />
          <DetailItem
            label="نسبة الكوتش"
            value={
              revenueSplit.coach_percentage != null
                ? `${Number(revenueSplit.coach_percentage)}%`
                : "-"
            }
          />
          <DetailItem
            label="حصة النادي"
            value={formatSubscriptionMoney(revenueSplit.club_amount)}
            tone="green"
          />
          <DetailItem
            label="نسبة النادي"
            value={
              revenueSplit.club_percentage != null
                ? `${Number(revenueSplit.club_percentage)}%`
                : "-"
            }
          />
        </DetailSection>
      )}

      <DetailSection title="سجل التعديل">
        <DetailItem
          label="الموظف الذي أضاف الاشتراك"
          value={getSubscriptionCreatorName(subscription)}
        />
        <DetailItem label="سبب آخر تعديل" value={subscription.reason || "-"} />
      </DetailSection>

      {subscription.notes && (
        <section className="space-y-2">
          <h3 className="text-sm font-medium text-app-yellow">الملاحظات</h3>
          <p className="whitespace-pre-wrap rounded-lg border border-app-line bg-app-card-soft/70 p-3 text-sm leading-6 text-app-text">
            {subscription.notes}
          </p>
        </section>
      )}

      {/* يعرض سجل الفترات التي جُمّد فيها الاشتراك. */}
      {subscription.freezes && subscription.freezes.length > 0 && (
        <div className="rounded-xl border border-app-line bg-app-card-soft/40 p-4 text-right space-y-2.5">
          <div className="flex items-center justify-between">
            <h4 className="text-xs font-semibold text-white">سجل تجميد الاشتراك</h4>
            <span className="text-[11px] text-app-muted-light">
              {subscription.freezes.length} {subscription.freezes.length === 1 ? "عملية تجميد" : "عمليات تجميد"}
            </span>
          </div>
          <div className="space-y-2">
            {subscription.freezes.map((f, i) => {
              const isOngoing = !f.actual_end_date;
              const displayDays = f.freeze_days || 1;
              return (
                <div
                  key={f.id || i}
                  className="flex items-center justify-between p-2.5 rounded-lg bg-black/30 text-xs text-app-text border border-app-line hover:border-app-line/80 transition-colors"
                >
                  <div className="space-y-0.5">
                    <div className="flex items-center gap-1.5 font-medium text-white">
                      <span>{formatDate(f.freeze_start_date)}</span>
                      <span className="text-app-muted-light">←</span>
                      <span>
                        {f.actual_end_date
                          ? formatDate(f.actual_end_date)
                          : f.freeze_end_date
                            ? `${formatDate(f.freeze_end_date)} (متوقع)`
                            : "مستمر حتى الآن"}
                      </span>
                    </div>
                    {f.reason && (
                      <p className="text-[11px] text-app-muted-light">
                        السبب: <span className="text-app-text">{f.reason}</span>
                      </p>
                    )}
                  </div>
                  <span
                    className={`px-2.5 py-0.5 rounded-full text-[11px] font-semibold border ${
                      isOngoing
                        ? "bg-cyan-500/15 text-cyan-400 border-cyan-500/30"
                        : "bg-app-yellow/10 text-app-yellow border-app-yellow/25"
                    }`}
                  >
                    {isOngoing
                      ? `مجمّد (${displayDays} يوم) ❄️`
                      : `${displayDays} يوم`}
                  </span>
                </div>
              );
            })}
          </div>
        </div>
      )}

      {/* يقيّد الإجراءات بحسب حالة الاشتراك الحالية. */}
      {showActions && (onFreeze || onUnfreeze || onCancel) && (
        <div className="border-t border-app-line pt-4 space-y-3">
          {subscription.status === "active" && (
            <>
              {!showFreezeForm ? (
                <div className="flex gap-3">
                  {onFreeze && (
                    <Button
                      type="button"
                      tone="warning"
                      className="flex-1 h-10 text-sm font-semibold"
                      onClick={() => setShowFreezeForm(true)}
                    >
                      تجميد الاشتراك
                    </Button>
                  )}
                  {onCancel && (
                    <Button
                      type="button"
                      tone="danger"
                      className="flex-1 h-10 text-sm font-semibold"
                      onClick={() => onCancel(subscription.id)}
                      loading={isCancelling}
                    >
                      إلغاء الاشتراك
                    </Button>
                  )}
                </div>
              ) : (
                <div className="rounded-xl border border-cyan-500/30 bg-gradient-to-b from-cyan-950/20 to-app-card-soft p-4 space-y-3.5 text-right shadow-lg">
                  <div className="flex items-center justify-between border-b border-app-line/60 pb-2">
                    <span className="text-[11px] text-cyan-400 font-medium">إيقاف مؤقت للاشتراك</span>
                    <h4 className="text-xs font-bold text-white flex items-center gap-1.5">
                      <span>❄️</span>
                      <span>تجميد الاشتراك الحالي</span>
                    </h4>
                  </div>

                  <div className="space-y-3">
                    {/* السطر الأول: تاريخ بدء التجميد وعدد الأيام */}
                    <div className="grid grid-cols-2 gap-3">
                      <div>
                        <DatePickerSmart
                          label="تاريخ بدء التجميد"
                          value={freezeStartDate}
                          onChange={(val) => {
                            setFreezeStartDate(val);
                            if (startDateError) setStartDateError("");
                          }}
                          compact={true}
                          error={Boolean(startDateError)}
                        />
                      </div>
                      <div>
                        <label className="block text-xs font-medium text-app-muted-light">
                          عدد الأيام <span className="text-[10px] text-app-muted font-normal">(اختياري)</span>
                          <input
                            type="number"
                            min="1"
                            max="365"
                            value={freezeDaysCount}
                            onChange={(e) => setFreezeDaysCount(e.target.value)}
                            placeholder="مثال: 7"
                            className="app-input mt-1.5 h-9 w-full px-2.5 text-center bg-black/40 text-white font-semibold text-xs rounded-lg border border-app-line focus:border-cyan-500 placeholder:text-app-muted/60"
                          />
                        </label>
                      </div>
                    </div>

                    {/* رسالة الخطأ على كامل العرض */}
                    {startDateError && (
                      <div className="rounded-lg bg-red-500/10 border border-red-500/25 p-2.5 text-right animate-in fade-in duration-200">
                        <p className="text-[11px] text-red-400 font-medium leading-relaxed flex items-center gap-1.5 justify-start">
                          <span>⚠️</span>
                          <span>{startDateError}</span>
                        </p>
                      </div>
                    )}

                    {/* السطر الثاني: تاريخ نهاية التجميد (على كامل العرض) */}
                    <div>
                      <label className="block text-xs font-medium text-app-muted-light">
                        تاريخ نهاية التجميد
                        <input
                          type="text"
                          value={calculatedFreezeEndDate ? formatDateDisplay(calculatedFreezeEndDate, "DD/MM/YYYY") : ""}
                          placeholder="DD/MM/YYYY"
                          readOnly
                          disabled
                          dir="ltr"
                          className="app-input mt-1.5 h-9 w-full px-3 text-center bg-black/20 text-cyan-300 font-semibold text-sm rounded-lg border border-cyan-500/20 cursor-not-allowed tracking-wider placeholder:text-app-muted/60"
                        />
                      </label>
                    </div>

                    {/* السطر الثالث: سبب التجميد (على كامل العرض وإجباري) */}
                    <div className="space-y-1">
                      <label className="block text-xs font-medium text-app-muted-light">
                        سبب التجميد <span className="text-red-400 font-bold text-sm">*</span>
                        <textarea
                          rows={2}
                          value={freezeReason}
                          onChange={(e) => {
                            setFreezeReason(e.target.value);
                            if (reasonError && e.target.value.trim()) setReasonError(false);
                          }}
                          placeholder="أدخل سبب التجميد بالتفصيل..."
                          maxLength={500}
                          className={`app-input mt-1.5 w-full p-2.5 text-right bg-black/40 text-white placeholder:text-app-muted text-xs rounded-lg border transition-colors ${
                            reasonError ? "border-red-500 focus:border-red-500" : "border-app-line focus:border-cyan-500"
                          }`}
                        />
                      </label>
                      {reasonError && (
                        <p className="text-[11px] text-red-400 font-medium text-right">
                          يرجى إدخال سبب التجميد قبل التأكيد.
                        </p>
                      )}
                    </div>
                  </div>

                  <div className="rounded-lg bg-cyan-950/30 border border-cyan-500/20 p-2.5 text-[11px] text-cyan-200/90 leading-relaxed">
                    💡 <strong>ملاحظة:</strong> سيبقى الاشتراك مجمداً وموقوفاً عن تسجيل الحضور حتى تضغط على زر <strong>&quot;إلغاء التجميد&quot;</strong>، وعندها سيقوم النظام تلقائياً بتمديد تاريخ نهاية الاشتراك بعدد أيام التجميد الفعلية.
                  </div>

                  <div className="flex gap-2 pt-1">
                    <Button
                      type="button"
                      tone="outline"
                      className="h-9 px-3 text-xs flex-1"
                      onClick={() => {
                        setShowFreezeForm(false);
                        setFreezeDaysCount("");
                        setFreezeReason("");
                        setReasonError(false);
                        setStartDateError("");
                      }}
                    >
                      تراجع
                    </Button>
                    <Button
                      type="button"
                      tone="warning"
                      className="h-9 px-3 text-xs flex-1 bg-cyan-600 hover:bg-cyan-500 text-white border-transparent"
                      loading={isFreezing}
                      onClick={() => {
                        if (!freezeStartDate) {
                          setStartDateError("يرجى تحديد تاريخ بدء التجميد.");
                          return;
                        }
                        if (subscription?.start_date && freezeStartDate < subscription.start_date) {
                          setStartDateError(`لا يمكن أن يكون تاريخ بدء التجميد قبل بداية الاشتراك (${subscription.start_date}).`);
                          return;
                        }
                        if (subscription?.end_date && freezeStartDate > subscription.end_date) {
                          setStartDateError(`لا يمكن تجميد اشتراك بعد تاريخ انتهائه (${subscription.end_date}).`);
                          return;
                        }
                        if (!freezeReason.trim()) {
                          setReasonError(true);
                          return;
                        }
                        const payload = {
                          freeze_start_date: freezeStartDate,
                          reason: freezeReason.trim(),
                        };
                        if (calculatedFreezeEndDate) {
                          payload.freeze_end_date = calculatedFreezeEndDate;
                        }
                        onFreeze(subscription.id, payload);
                        setShowFreezeForm(false);
                        setFreezeDaysCount("");
                        setFreezeReason("");
                        setReasonError(false);
                        setStartDateError("");
                      }}
                    >
                      تأكيد التجميد ❄️
                    </Button>
                  </div>
                </div>
              )}
            </>
          )}

          {subscription.status === "frozen" && onUnfreeze && (
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
      )}
    </div>
  );
}
