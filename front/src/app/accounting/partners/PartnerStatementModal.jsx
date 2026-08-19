"use client";

import { useState } from "react";
import Modal from "@/components/ui/Modal";
import Button from "@/components/ui/Button";
import LoadingSpinner from "@/components/ui/LoadingSpinner";
import { useGetPartnerStatementQuery } from "@/lib/api/accountingApi";
import { Field } from "@/components/forms/FormControls";

export default function PartnerStatementModal({ isOpen, onClose, partner }) {
  const [fromDate, setFromDate] = useState("");
  const [toDate, setToDate] = useState("");

  const { data, isLoading, isFetching } = useGetPartnerStatementQuery(
    {
      id: partner?.id,
      from_date: fromDate || undefined,
      to_date: toDate || undefined,
    },
    { skip: !partner?.id || !isOpen }
  );

  const statement = data?.data || {};
  const movements = statement.entries || statement.movements || statement.data || [];
  const capitalBalance = Number(statement.capital_balance || 0);
  const drawingsBalance = Number(statement.drawings_balance || 0);
  const netEquity = Number(statement.net_equity || (capitalBalance - drawingsBalance));

  return (
    <Modal
      open={isOpen}
      onClose={onClose}
      title={`كشف حساب الشريك: ${partner?.name}`}
      size="xl"
    >
      <div className="space-y-4" dir="rtl">
        {/* Balance Overview */}
        <div className="flex flex-wrap items-end justify-between gap-3 rounded-xl border border-app-line/40 bg-app-card-soft/40 p-3.5">
          <div className="flex flex-wrap items-center gap-3">
            <div className="w-36">
              <Field
                label="من تاريخ"
                type="date"
                value={fromDate}
                onChange={(e) => setFromDate(e.target.value)}
              />
            </div>
            <div className="w-36">
              <Field
                label="إلى تاريخ"
                type="date"
                value={toDate}
                onChange={(e) => setToDate(e.target.value)}
              />
            </div>
          </div>

          <div className="flex items-center gap-3 text-xs">
            <div className="rounded-lg bg-app-panel px-3 py-1.5 border border-app-line/30">
              <span className="text-app-muted block">رصيد رأس المال:</span>
              <span className="font-bold text-emerald-400 font-mono">
                ${capitalBalance.toLocaleString()}
              </span>
            </div>
            <div className="rounded-lg bg-app-panel px-3 py-1.5 border border-app-line/30">
              <span className="text-app-muted block">إجمالي المسحوبات:</span>
              <span className="font-bold text-rose-400 font-mono">
                ${drawingsBalance.toLocaleString()}
              </span>
            </div>
            <div className="rounded-lg bg-app-panel px-3 py-1.5 border border-app-line/30">
              <span className="text-app-muted block">صافي حقوق الشريك:</span>
              <span className="font-bold text-app-yellow font-mono">
                ${netEquity.toLocaleString()}
              </span>
            </div>
          </div>
        </div>

        {/* Entries Table */}
        {isLoading || isFetching ? (
          <div className="flex h-48 items-center justify-center">
            <LoadingSpinner />
          </div>
        ) : movements.length === 0 ? (
          <div className="rounded-xl border border-dashed border-app-line/60 p-8 text-center text-sm text-app-muted">
            لا توجد حركات أو قيود مسجلة على حسابات هذا الشريك في الفترة المحددة.
          </div>
        ) : (
          <div className="max-h-96 overflow-y-auto rounded-xl border border-app-line/40 sidebar-scrollbar">
            <table className="w-full text-right text-xs">
              <thead className="sticky top-0 bg-app-panel text-app-muted border-b border-app-line/50">
                <tr>
                  <th className="p-2.5">التاريخ</th>
                  <th className="p-2.5">رقم السند</th>
                  <th className="p-2.5">الحساب المتأثر</th>
                  <th className="p-2.5">البيان / الشرح</th>
                  <th className="p-2.5 text-left">مدين</th>
                  <th className="p-2.5 text-left">دائن</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-app-line/20 bg-app-card-soft/20 text-app-text">
                {movements.map((row, idx) => (
                  <tr key={row.id || idx} className="hover:bg-app-line/10 transition-colors">
                    <td className="p-2.5 whitespace-nowrap text-app-muted font-mono">{row.date}</td>
                    <td className="p-2.5 whitespace-nowrap font-mono font-medium text-app-yellow">
                      {row.number || `#${row.journal_id || row.id}`}
                    </td>
                    <td className="p-2.5 text-app-text font-medium">{row.account_name || "-"}</td>
                    <td className="p-2.5 text-app-muted max-w-xs truncate">{row.memo || row.description || "-"}</td>
                    <td className="p-2.5 text-left font-mono text-emerald-400 font-medium">
                      {Number(row.debit) > 0 ? `$${Number(row.debit).toLocaleString()}` : "-"}
                    </td>
                    <td className="p-2.5 text-left font-mono text-rose-400 font-medium">
                      {Number(row.credit) > 0 ? `$${Number(row.credit).toLocaleString()}` : "-"}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

        <div className="flex justify-end pt-2">
          <Button variant="secondary" onClick={onClose}>
            إغلاق
          </Button>
        </div>
      </div>
    </Modal>
  );
}
