"use client";

import { useState } from "react";
import Modal from "@/components/ui/Modal";
import Button from "@/components/ui/Button";
import LoadingSpinner from "@/components/ui/LoadingSpinner";
import { useGetAccountLedgerQuery } from "@/lib/api/accountingApi";
import { Field } from "@/components/forms/FormControls";

export default function AccountLedgerModal({ isOpen, onClose, account }) {
  if (!account) return null;
  const [fromDate, setFromDate] = useState("");
  const [toDate, setToDate] = useState("");


  const { data, isLoading, isFetching, refetch } = useGetAccountLedgerQuery(
    {
      id: account?.id,
      from_date: fromDate || undefined,
      to_date: toDate || undefined,
    },
    { skip: !account?.id || !isOpen }
  );

  const ledgerData = data?.data || {};
  const entries = ledgerData.entries || ledgerData.data || [];
  const openingUsd = Number(ledgerData.opening_balance_usd || 0);
  const openingSyp = Number(ledgerData.opening_balance_syp || 0);
  const totalDebitUsd = Number(ledgerData.total_debit_usd || 0);
  const totalCreditUsd = Number(ledgerData.total_credit_usd || 0);
  const closingUsd = Number(ledgerData.closing_balance_usd || (openingUsd + totalDebitUsd - totalCreditUsd));
  const closingSyp = Number(ledgerData.closing_balance_syp || 0);

  return (
    <Modal
      open={isOpen}
      onClose={onClose}
      title={`دفتر الأستاذ (كشف حساب): ${account?.code} - ${account?.name}`}
      size="xl"
    >
      <div className="space-y-4" dir="rtl">
        {/* Filters & Summary Row */}
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
            <Button
              variant="secondary"
              onClick={() => {
                setFromDate("");
                setToDate("");
              }}
              className="mt-5"
            >
              إعادة ضبط
            </Button>
          </div>

          <div className="flex items-center gap-4 text-xs">
            <div className="rounded-lg bg-app-panel px-3 py-1.5 border border-app-line/30">
              <span className="text-app-muted block">الرصيد الافتتاحي:</span>
              <span className="font-bold text-app-text font-mono">
                ${openingUsd.toLocaleString()} / {openingSyp.toLocaleString()} ل.س
              </span>
            </div>
            <div className="rounded-lg bg-app-panel px-3 py-1.5 border border-app-line/30">
              <span className="text-app-muted block">الرصيد النهائي:</span>
              <span className={`font-bold font-mono ${closingUsd >= 0 ? "text-emerald-400" : "text-rose-400"}`}>
                ${closingUsd.toLocaleString()} / {closingSyp.toLocaleString()} ل.س
              </span>
            </div>
          </div>
        </div>

        {/* Ledger Entries Table */}
        {isLoading || isFetching ? (
          <div className="flex h-48 items-center justify-center">
            <LoadingSpinner />
          </div>
        ) : entries.length === 0 ? (
          <div className="rounded-xl border border-dashed border-app-line/60 p-8 text-center text-sm text-app-muted">
            لا توجد حركات أو قيود مالية مسجلة على هذا الحساب في الفترة المحددة.
          </div>
        ) : (
          <div className="max-h-96 overflow-y-auto rounded-xl border border-app-line/40 sidebar-scrollbar">
            <table className="w-full text-right text-xs">
              <thead className="sticky top-0 bg-app-panel text-app-muted border-b border-app-line/50">
                <tr>
                  <th className="p-2.5">التاريخ</th>
                  <th className="p-2.5">رقم السند</th>
                  <th className="p-2.5">البيان / الشرح</th>
                  <th className="p-2.5 text-left">مدين (USD)</th>
                  <th className="p-2.5 text-left">دائن (USD)</th>
                  <th className="p-2.5 text-left">مدين (SYP)</th>
                  <th className="p-2.5 text-left">دائن (SYP)</th>
                  <th className="p-2.5 text-left">الرصيد التراكمي</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-app-line/20 bg-app-card-soft/20 text-app-text">
                {entries.map((row, idx) => {
                  const debitUsd = Number(row.debit_usd || 0);
                  const creditUsd = Number(row.credit_usd || 0);
                  const debitSyp = Number(row.debit_syp || 0);
                  const creditSyp = Number(row.credit_syp || 0);

                  return (
                    <tr key={row.id || idx} className="hover:bg-app-line/10 transition-colors">
                      <td className="p-2.5 whitespace-nowrap text-app-muted font-mono">{row.date || row.journal?.date}</td>
                      <td className="p-2.5 whitespace-nowrap font-mono font-medium text-app-yellow">
                        {row.journal?.number || row.journal_number || `#${row.journal_id}`}
                      </td>
                      <td className="p-2.5 text-app-text max-w-xs truncate" title={row.memo || row.journal?.description}>
                        {row.memo || row.journal?.description || "-"}
                      </td>
                      <td className="p-2.5 text-left font-mono text-emerald-400 font-medium">
                        {debitUsd > 0 ? `$${debitUsd.toLocaleString()}` : "-"}
                      </td>
                      <td className="p-2.5 text-left font-mono text-rose-400 font-medium">
                        {creditUsd > 0 ? `$${creditUsd.toLocaleString()}` : "-"}
                      </td>
                      <td className="p-2.5 text-left font-mono text-emerald-400">
                        {debitSyp > 0 ? `${debitSyp.toLocaleString()}` : "-"}
                      </td>
                      <td className="p-2.5 text-left font-mono text-rose-400">
                        {creditSyp > 0 ? `${creditSyp.toLocaleString()}` : "-"}
                      </td>
                      <td className="p-2.5 text-left font-mono font-bold text-app-text">
                        {row.running_balance_usd !== undefined ? `$${Number(row.running_balance_usd).toLocaleString()}` : "-"}
                      </td>
                    </tr>
                  );
                })}
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
