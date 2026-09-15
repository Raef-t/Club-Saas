"use client";

import { useMemo } from "react";
import { formatDate } from "@/lib/utils";
import {
  formatSubscriptionMoney,
  getSubscriptionReceiptNumbers,
  isPrivateSubscriptionPlan,
  parseSubscriptionAmount,
} from "@/app/management/subscriptions/subscriptionUtils";

const PAYMENT_METHOD_LABELS = {
  cash: "نقداً",
  card: "بطاقة دفع",
  wallet: "المحفظة",
  bank_transfer: "تحويل بنكي",
};

// ─── helpers ───────────────────────────────────────────────────────────────

function getPlanName(sub) {
  return typeof sub.plan?.name === "object"
    ? sub.plan?.name?.ar || sub.plan?.name?.en
    : sub.plan?.name || sub.plan_name || "اشتراك";
}

/**
 * Resolves the paid amounts for the two halves of a private subscription.
 * Priority: dedicated fields → revenue_split → payment reasons → plan prices.
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
  const norm = (p) => String(p?.reason || "").trim().toLowerCase();
  const coachPay = allPayments.find((p) => {
    const r = norm(p);
    return r.includes("المدرب") || r.includes("الكوتش") || r.includes("coach");
  });
  const branchPay = allPayments.find((p) => {
    const r = norm(p);
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
 * Builds a flat list of payment row objects.
 * A private subscription produces ONE row (type = "private") containing both
 * coach and branch receipt details. Regular subscriptions produce the usual
 * single row (type = "regular").
 */
function buildPaymentRows(subscriptions) {
  const list = [];

  subscriptions.forEach((sub) => {
    const planName = getPlanName(sub);
    const date = sub.created_at || sub.start_date;
    const method = sub.payment_method || "cash";

    const isPrivate = isPrivateSubscriptionPlan(sub.plan || sub);
    const { coachReceiptNumber, branchReceiptNumber } = getSubscriptionReceiptNumbers(sub);
    const hasPrivateReceipts = isPrivate && (coachReceiptNumber || branchReceiptNumber);

    // Discount info (applies to both private and regular)
    const isDiscount = Boolean(sub.is_discount);
    const discountPct = parseSubscriptionAmount(sub.discount_percentage);
    const coachDiscountPct = parseSubscriptionAmount(sub.coach_discount_percentage);
    const branchDiscountPct = parseSubscriptionAmount(sub.branch_discount_percentage);

    if (hasPrivateReceipts) {
      const { coachAmount, branchAmount } = resolvePrivateAmounts(sub);
      list.push({
        id: `private-${sub.id}`,
        type: "private",
        planName,
        date,
        method,
        totalAmount: Number(sub.paid_amount || 0),
        isDiscount,
        discountPct,
        coach: {
          receiptNumber: coachReceiptNumber,
          amount: coachAmount,
          discountPct: coachDiscountPct,
        },
        branch: {
          receiptNumber: branchReceiptNumber,
          amount: branchAmount,
          discountPct: branchDiscountPct,
        },
      });
      return;
    }

    // Regular subscription — always show, even if paid_amount is 0 or null
    if (Array.isArray(sub.payments) && sub.payments.length > 0) {
      sub.payments.forEach((p, idx) => {
        list.push({
          id: p.id || `${sub.id}-${idx}`,
          type: "regular",
          planName,
          date: p.payment_date || p.created_at || date,
          method: p.payment_method || method,
          amount: Number(p.amount ?? p.paid_amount ?? 0),
          receiptNumber: p.receipt_number || sub.receipt_number || null,
          isDiscount,
          discountPct,
        });
      });
    } else {
      // No payment records yet — still show the subscription row
      list.push({
        id: `sub-${sub.id}`,
        type: "regular",
        planName,
        date,
        method,
        amount: Number(sub.paid_amount || 0),
        receiptNumber: sub.receipt_number || null,
        isDiscount,
        discountPct,
      });
    }
  });

  return list.sort((a, b) => new Date(b.date || 0) - new Date(a.date || 0));
}

// ─── sub-components ────────────────────────────────────────────────────────

/** Small inline badge for the discount percentage */
function DiscountBadge({ pct }) {
  if (!pct || pct <= 0) return null;
  return (
    <span className="rounded-full border border-app-red/30 bg-app-red/10 px-2 py-0.5 text-[10px] font-semibold text-app-red">
      حسم {pct}%
    </span>
  );
}

/** One receipt line: label · receipt-number · amount */
function ReceiptLine({ label, receiptNumber, amount, discountPct, tone }) {
  const colors =
    tone === "coach"
      ? { text: "text-blue-300", badge: "border-blue-400/25 bg-blue-400/10 text-blue-200" }
      : { text: "text-app-yellow", badge: "border-app-yellow/25 bg-app-yellow/10 text-app-yellow" };

  return (
    <div className="flex flex-wrap items-center gap-2">
      {/* label */}
      <span className={`text-xs font-medium ${colors.text}`}>{label}:</span>

      {/* receipt number */}
      {receiptNumber ? (
        <span
          className={`rounded-md border px-2 py-0.5 font-mono text-[11px] ${colors.badge}`}
          dir="ltr"
        >
          {receiptNumber}
        </span>
      ) : (
        <span className="text-[11px] text-app-muted-light">-</span>
      )}

      {/* discount badge */}
      <DiscountBadge pct={discountPct} />

      {/* amount pushed to the right */}
      <span className={`ms-auto text-sm font-bold ${colors.text}`}>
        {formatSubscriptionMoney(amount)}
      </span>
    </div>
  );
}

// ─── card for a PRIVATE subscription ──────────────────────────────────────

function PrivatePaymentCard({ row }) {
  const methodLabel = PAYMENT_METHOD_LABELS[row.method] || row.method || "نقداً";

  return (
    <div className="rounded-xl border border-app-line bg-app-card-soft/60 p-4 transition hover:border-app-yellow/30 hover:bg-app-card-soft">
      {/* top row: plan name + meta + total */}
      <div className="flex flex-wrap items-start justify-between gap-2">
        <div className="min-w-0">
          <div className="flex flex-wrap items-center gap-2">
            <p className="text-sm font-semibold text-white">{row.planName}</p>
            {row.isDiscount && row.discountPct > 0 && (
              <DiscountBadge pct={row.discountPct} />
            )}
          </div>
          <p className="mt-0.5 text-xs text-app-muted-light">
            {row.date ? formatDate(row.date) : "-"} • {methodLabel}
          </p>
        </div>
        <div className="text-right">
          <p className="text-[10px] text-app-muted-light">الإجمالي المدفوع</p>
          <p className="text-sm font-bold text-app-green">
            {formatSubscriptionMoney(row.totalAmount)}
          </p>
        </div>
      </div>

      {/* divider */}
      <div className="mt-3 space-y-2 border-t border-app-line pt-3">
        <ReceiptLine
          label="إيصال الكوتش"
          receiptNumber={row.coach.receiptNumber}
          amount={row.coach.amount}
          discountPct={row.coach.discountPct}
          tone="coach"
        />
        <ReceiptLine
          label="إيصال النادي"
          receiptNumber={row.branch.receiptNumber}
          amount={row.branch.amount}
          discountPct={row.branch.discountPct}
          tone="branch"
        />
      </div>
    </div>
  );
}

// ─── card for a REGULAR subscription ──────────────────────────────────────

function RegularPaymentCard({ row }) {
  const methodLabel = PAYMENT_METHOD_LABELS[row.method] || row.method || "نقداً";

  return (
    <div className="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-app-line bg-app-card-soft/60 p-4 transition hover:border-app-yellow/40 hover:bg-app-card-soft">
      {/* left: icon + info */}
      <div className="flex items-center gap-3">
        <div className="grid size-10 place-items-center rounded-xl bg-app-green/10 text-app-green">
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
        </div>
        <div>
          <div className="flex flex-wrap items-center gap-2">
            <p className="text-sm font-semibold text-white">{row.planName}</p>
            {row.isDiscount && <DiscountBadge pct={row.discountPct} />}
          </div>
          <p className="text-xs text-app-muted-light mt-0.5">
            {row.date ? formatDate(row.date) : "-"} • {methodLabel}
          </p>
        </div>
      </div>

      {/* right: receipt + amount */}
      <div className="flex items-center gap-3 text-xs">
        {row.receiptNumber && (
          <div className="flex items-center gap-1.5 rounded-lg bg-black/25 px-2 py-1">
            <span className="text-[10px] text-app-muted-light">إيصال:</span>
            <span className="font-mono text-[11px] text-app-muted-light" dir="ltr">
              {row.receiptNumber}
            </span>
          </div>
        )}
        <span className="text-base font-bold text-app-green">
          {formatSubscriptionMoney(row.amount)}
        </span>
      </div>
    </div>
  );
}

// ─── main component ────────────────────────────────────────────────────────

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
    () =>
      payments.reduce((sum, row) => {
        const amt = row.type === "private" ? row.totalAmount : row.amount;
        return sum + (amt || 0);
      }, 0),
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
          {payments.map((row) =>
            row.type === "private" ? (
              <PrivatePaymentCard key={row.id} row={row} />
            ) : (
              <RegularPaymentCard key={row.id} row={row} />
            ),
          )}
        </div>
      )}
    </div>
  );
}
