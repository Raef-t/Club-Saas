"use client";

import { useMemo } from "react";
import { formatDate } from "@/lib/utils";
import { formatSubscriptionMoney } from "@/app/management/subscriptions/subscriptionUtils";

const PAYMENT_METHOD_LABELS = {
  cash: "نقداً",
  card: "بطاقة دفع",
  wallet: "المحفظة",
  bank_transfer: "تحويل بنكي",
};

export default function PlayerPaymentsTab({ subscriptions = [], isLoading, branchId }) {
  const filteredSubscriptions = useMemo(() => {
    if (!branchId) return subscriptions;
    return subscriptions.filter(
      (s) => !s.branch_id || String(s.branch_id) === String(branchId),
    );
  }, [subscriptions, branchId]);

  const payments = useMemo(() => {
    const list = [];
    filteredSubscriptions.forEach((sub) => {
      const planName =
        typeof sub.plan?.name === "object"
          ? sub.plan?.name?.ar || sub.plan?.name?.en
          : sub.plan?.name || sub.plan_name || "اشتراك";

      if (Array.isArray(sub.payments) && sub.payments.length > 0) {
        sub.payments.forEach((p, idx) => {
          list.push({
            id: p.id || `${sub.id}-${idx}`,
            planName,
            amount: Number(p.amount ?? p.paid_amount ?? 0),
            date: p.payment_date || p.created_at || sub.created_at,
            method: p.payment_method || sub.payment_method || "cash",
            receiptNumber: p.receipt_number || sub.receipt_number || "-",
          });
        });
      } else if (Number(sub.paid_amount) > 0) {
        list.push({
          id: `sub-${sub.id}`,
          planName,
          amount: Number(sub.paid_amount),
          date: sub.created_at || sub.start_date,
          method: sub.payment_method || "cash",
          receiptNumber: sub.receipt_number || "-",
        });
      }
    });

    return list.sort((a, b) => new Date(b.date || 0) - new Date(a.date || 0));
  }, [subscriptions]);

  const totalPaid = useMemo(() => {
    return payments.reduce((sum, p) => sum + (p.amount || 0), 0);
  }, [payments]);

  return (
    <div className="space-y-6">
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
            const methodLabel = PAYMENT_METHOD_LABELS[payment.method] || payment.method || "نقداً";

            return (
              <div
                key={payment.id}
                className="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-app-line bg-app-card-soft/60 p-4 transition hover:border-app-yellow/40 hover:bg-app-card-soft"
              >
                <div className="flex items-center gap-3">
                  <div className="grid size-10 place-items-center rounded-xl bg-app-green/10 text-app-green">
                    <svg className="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7">
                      <rect x="2" y="5" width="20" height="14" rx="2" />
                      <line x1="2" y1="10" x2="22" y2="10" />
                      <circle cx="6.5" cy="14.5" r="1" fill="currentColor" />
                    </svg>
                  </div>
                  <div>
                    <p className="text-sm font-semibold text-white">{payment.planName}</p>
                    <p className="text-xs text-app-muted-light mt-0.5">
                      {payment.date ? formatDate(payment.date) : "-"} • طريقة الدفع: {methodLabel}
                    </p>
                  </div>
                </div>

                <div className="flex items-center gap-4 text-xs">
                  {payment.receiptNumber && payment.receiptNumber !== "-" && (
                    <div className="rounded-lg bg-black/25 px-2 py-1 font-mono text-[11px] text-app-muted-light">
                      {payment.receiptNumber}
                    </div>
                  )}
                  <span className="text-base font-bold text-app-green">
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
