"use client";

import { useMemo } from "react";
import { formatDate } from "@/lib/utils";
import {
  formatSubscriptionMoney,
  getSubscriptionReceiptNumbers,
  isPrivateSubscriptionPlan,
} from "@/app/management/subscriptions/subscriptionUtils";

const PAYMENT_METHOD_LABELS = {
  cash: "نقداً",
  card: "بطاقة دفع",
  wallet: "المحفظة",
  bank_transfer: "تحويل بنكي",
};

/**
 * Resolves the paid amounts for the coach and branch halves of a private
 * subscription. Priority order:
 *   1. Dedicated paid-amount fields  (coach_paid_amount / branch_paid_amount)
 *   2. revenue_split snapshot        (coach_amount / club_amount)
 *   3. Individual payment records keyed by Arabic reason keywords
 *   4. Plan split prices             (coach_price / branch_price)
 */
function resolvePrivateAmounts(sub) {
  if (sub.coach_paid_amount != null && sub.branch_paid_amount != null) {
    return {
      coachAmount: Number(sub.coach_paid_amount),
      branchAmount: Number(sub.branch_paid_amount),
    };
  }

  const rs = sub.revenue_split;
  if (rs) {
    const coachAmt = rs.coach_amount ?? rs.coach_paid_amount;
    const branchAmt = rs.club_amount ?? rs.branch_amount ?? rs.branch_paid_amount;
    if (coachAmt != null && branchAmt != null) {
      return { coachAmount: Number(coachAmt), branchAmount: Number(branchAmt) };
    }
  }

  const allPayments = Array.isArray(sub.payments) ? sub.payments : [];
  const normalize = (p) =>
    String(p?.reason || "")
      .trim()
      .toLowerCase();

  const coachPay = allPayments.find((p) => {
    const r = normalize(p);
    return r.includes("المدرب") || r.includes("الكوتش") || r.includes("coach");
  });
  const branchPay = allPayments.find((p) => {
    const r = normalize(p);
    return r.includes("النادي") || r.includes("الفرع") || r.includes("branch");
  });

  if (coachPay || branchPay) {
    const total = Number(sub.paid_amount || 0);
    const coachAmt = coachPay ? Number(coachPay.amount ?? 0) : 0;
    const branchAmt = branchPay
      ? Number(branchPay.amount ?? 0)
      : Math.max(0, total - coachAmt);
    return { coachAmount: coachAmt, branchAmount: branchAmt };
  }

  const plan = sub.plan || {};
  return {
    coachAmount: Number(plan.coach_price ?? 0),
    branchAmount: Number(plan.branch_price ?? 0),
  };
}

/**
 * Builds the flat list of payment rows.
 * Private subscriptions with two receipts produce two rows each.
 */
function buildPaymentRows(subscriptions) {
  const list = [];

  subscriptions.forEach((sub) => {
    const planName =
      typeof sub.plan?.name === "object"
        ? sub.plan?.name?.ar || sub.plan?.name?.en
        : sub.plan?.name || sub.plan_name || "اشتراك";

    const date = sub.created_at || sub.start_date;
    const method = sub.payment_method || "cash";

    const isPrivate = isPrivateSubscriptionPlan(sub.plan || sub);
    const { coachReceiptNumber, branchReceiptNumber } = getSubscriptionReceiptNumbers(sub);
    const hasPrivateReceipts = isPrivate && (coachReceiptNumber || branchReceiptNumber);

    if (hasPrivateReceipts) {
      const { coachAmount, branchAmount } = resolvePrivateAmounts(sub);

      if (coachReceiptNumber || coachAmount > 0) {
        list.push({
          id: `coach-${sub.id}`,
          planName,
          label: "إيصال الكوتش",
          tone: "coach",
          amount: coachAmount,
          date,
          method,
          receiptNumber: coachReceiptNumber,
        });
      }

      if (branchReceiptNumber || branchAmount > 0) {
        list.push({
          id: `branch-${sub.id}`,
          planName,
          label: "إيصال النادي",
          tone: "branch",
          amount: branchAmount,
          date,
          method,
          receiptNumber: branchReceiptNumber,
        });
      }

      return;
    }

    // Non-private subscription – keep original behaviour
    if (Array.isArray(sub.payments) && sub.payments.length > 0) {
      sub.payments.forEach((p, idx) => {
        list.push({
          id: p.id || `${sub.id}-${idx}`,
          planName,
          label: null,
          tone: "default",
          amount: Number(p.amount ?? p.paid_amount ?? 0),
          date: p.payment_date || p.created_at || date,
          method: p.payment_method || method,
          receiptNumber: p.receipt_number || sub.receipt_number || "-",
        });
      });
    } else if (Number(sub.paid_amount) > 0) {
      list.push({
        id: `sub-${sub.id}`,
        planName,
        label: null,
        tone: "default",
        amount: Number(sub.paid_amount),
        date,
        method,
        receiptNumber: sub.receipt_number || "-",
      });
    }
  });

  return list.sort((a, b) => new Date(b.date || 0) - new Date(a.date || 0));
}

// ─── icon ──────────────────────────────────────────────────────────────────

function PaymentIcon({ tone }) {
  if (tone === "coach") {
    return (
      <svg
        className="size-5"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        strokeWidth="1.7"
        aria-hidden="true"
      >
        <rect x="2" y="10" width="2" height="4" rx="1" />
        <rect x="20" y="10" width="2" height="4" rx="1" />
        <rect x="4" y="8" width="3" height="8" rx="1" />
        <rect x="17" y="8" width="3" height="8" rx="1" />
        <line x1="7" y1="12" x2="17" y2="12" />
      </svg>
    );
  }

  if (tone === "branch") {
    return (
      <svg
        className="size-5"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        strokeWidth="1.7"
        aria-hidden="true"
      >
        <rect x="3" y="7" width="18" height="14" rx="1" />
        <path d="M3 7l9-4 9 4" />
        <rect x="9" y="13" width="6" height="8" rx="0.5" />
      </svg>
    );
  }

  return (
    <svg
      className="size-5"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="1.7"
      aria-hidden="true"
    >
      <rect x="2" y="5" width="20" height="14" rx="2" />
      <line x1="2" y1="10" x2="22" y2="10" />
      <circle cx="6.5" cy="14.5" r="1" fill="currentColor" />
    </svg>
  );
}

// ─── tone styling ──────────────────────────────────────────────────────────

const TONE = {
  coach: {
    card: "border-blue-400/20 hover:border-blue-400/40",
    icon: "bg-blue-500/10 text-blue-300",
    badge: "border-blue-400/25 bg-blue-400/10 text-blue-200",
    amount: "text-blue-300",
  },
  branch: {
    card: "border-app-yellow/20 hover:border-app-yellow/40",
    icon: "bg-app-yellow/10 text-app-yellow",
    badge: "border-app-yellow/25 bg-app-yellow/10 text-app-yellow",
    amount: "text-app-yellow",
  },
  default: {
    card: "border-app-line hover:border-app-yellow/40",
    icon: "bg-app-green/10 text-app-green",
    badge: "border-app-line bg-black/25 text-app-muted-light",
    amount: "text-app-green",
  },
};

// ─── component ─────────────────────────────────────────────────────────────

export default function PlayerPaymentsTab({ subscriptions = [], isLoading, branchId }) {
  const filteredSubscriptions = useMemo(() => {
    if (!branchId) return subscriptions;
    return subscriptions.filter(
      (s) => !s.branch_id || String(s.branch_id) === String(branchId),
    );
  }, [subscriptions, branchId]);

  const payments = useMemo(
    () => buildPaymentRows(filteredSubscriptions),
    [filteredSubscriptions],
  );

  const totalPaid = useMemo(
    () => payments.reduce((sum, p) => sum + (p.amount || 0), 0),
    [payments],
  );

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-base font-semibold text-white">المدفوعات والإيصالات</h2>
          <p className="text-xs text-app-muted-light mt-0.5">
            سجل المبالغ المدفوعة للاشتراكات والإيصالات المالية الخاصة بحسابك.
          </p>
        </div>
        <div className="rounded-xl border border-app-line bg-app-card-soft px-3.5 py-1.5 text-center">
          <p className="text-[11px] text-app-muted-light">إجمالي المدفوعات</p>
          <p className="text-base font-bold text-app-green">
            {formatSubscriptionMoney(totalPaid)}
          </p>
        </div>
      </div>

      {/* Content */}
      {isLoading ? (
        <div className="space-y-3">
          {[1, 2, 3].map((item) => (
            <div key={item} className="h-16 animate-pulse rounded-xl bg-app-card-soft/50" />
          ))}
        </div>
      ) : payments.length === 0 ? (
        <div className="rounded-2xl border border-dashed border-app-line p-8 text-center text-sm text-app-muted-light">
          لا توجد مدفوعات مسجلة حتى الآن.
        </div>
      ) : (
        <div className="space-y-2.5">
          {payments.map((payment) => {
            const methodLabel =
              PAYMENT_METHOD_LABELS[payment.method] || payment.method || "نقداً";
            const tc = TONE[payment.tone] || TONE.default;

            return (
              <div
                key={payment.id}
                className={`flex flex-wrap items-center justify-between gap-3 rounded-xl border bg-app-card-soft/60 p-4 transition hover:bg-app-card-soft ${tc.card}`}
              >
                {/* Left: icon + info */}
                <div className="flex items-center gap-3">
                  <div className={`grid size-10 place-items-center rounded-xl ${tc.icon}`}>
                    <PaymentIcon tone={payment.tone} />
                  </div>
                  <div>
                    <div className="flex flex-wrap items-center gap-2">
                      <p className="text-sm font-semibold text-white">{payment.planName}</p>
                      {payment.label && (
                        <span
                          className={`rounded-full border px-2 py-0.5 text-[10px] font-medium ${tc.badge}`}
                        >
                          {payment.label}
                        </span>
                      )}
                    </div>
                    <p className="text-xs text-app-muted-light mt-0.5">
                      {payment.date ? formatDate(payment.date) : "-"} • طريقة الدفع:{" "}
                      {methodLabel}
                    </p>
                  </div>
                </div>

                {/* Right: receipt badge + amount */}
                <div className="flex items-center gap-4 text-xs">
                  {payment.receiptNumber && payment.receiptNumber !== "-" && (
                    <div className={`rounded-lg border px-2 py-1 font-mono text-[11px] ${tc.badge}`}>
                      {payment.receiptNumber}
                    </div>
                  )}
                  <span className={`text-base font-bold ${tc.amount}`}>
                    {formatSubscriptionMoney(payment.amount)}
                  </span>
                </div>
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
}
