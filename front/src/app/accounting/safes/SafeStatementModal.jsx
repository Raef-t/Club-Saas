"use client";

import { useState } from "react";
import Modal from "@/components/ui/Modal";
import Button from "@/components/ui/Button";
import LoadingSpinner from "@/components/ui/LoadingSpinner";
import { useGetSafeStatementQuery } from "@/lib/api/accountingApi";
import { Field } from "@/components/forms/FormControls";

export default function SafeStatementModal({ isOpen, onClose, safe }) {
  if (!safe) return null;

  const [fromDate, setFromDate] = useState("");

  const [toDate, setToDate] = useState("");

  const { data, isLoading, isFetching } = useGetSafeStatementQuery(
    {
      id: safe?.id,
      from_date: fromDate || undefined,
      to_date: toDate || undefined,
    },
    { skip: !safe?.id || !isOpen }
  );

  const statement = data?.data || {};
  const movements = statement.movements || statement.data || statement.entries || [];
  const openingBalance = Number(statement.opening_balance || 0);
  const totalIn = Number(statement.total_in || statement.total_receipts || 0);
  const totalOut = Number(statement.total_out || statement.total_payments || 0);
  const closingBalance = Number(statement.closing_balance || (openingBalance + totalIn - totalOut));

  const currencySymbol = safe?.currency === "USD" ? "$" : "ل.س";

  return (
    <Modal
      open={isOpen}
      onClose={onClose}
      title={`كشف حركة الصندوق: ${safe?.name} (${safe?.currency})`}
      size="xl"
    >
      <div className="space-y-4" dir="rtl">
        {/* Filters & Balance Overview */}
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

          <div className="flex items-center gap-3 text-xs">
            <div className="rounded-lg bg-app-panel px-3 py-1.5 border border-app-line/30">
              <span className="text-app-muted block">الرصيد الافتتاحي:</span>
              <span className="font-bold text-app-text font-mono">
                {currencySymbol} {openingBalance.toLocaleString()}
              </span>
            </div>
            <div className="rounded-lg bg-app-panel px-3 py-1.5 border border-app-line/30">
              <span className="text-app-muted block">إجمالي المقبوضات:</span>
              <span className="font-bold text-emerald-400 font-mono">
                + {currencySymbol} {totalIn.toLocaleString()}
              </span>
            </div>
            <div className="rounded-lg bg-app-panel px-3 py-1.5 border border-app-line/30">
              <span className="text-app-muted block">إجمالي المدفوعات:</span>
              <span className="font-bold text-rose-400 font-mono">
                - {currencySymbol} {totalOut.toLocaleString()}
              </span>
            </div>
            <div className="rounded-lg bg-app-panel px-3 py-1.5 border border-app-line/30">
              <span className="text-app-muted block">الرصيد الختامي:</span>
              <span className={`font-bold font-mono ${closingBalance >= 0 ? "text-app-yellow" : "text-rose-400"}`}>
                {currencySymbol} {closingBalance.toLocaleString()}
              </span>
            </div>
          </div>
        </div>

        {/* Movements Table */}
        {isLoading || isFetching ? (
          <div className="flex h-48 items-center justify-center">
            <LoadingSpinner />
          </div>
        ) : movements.length === 0 ? (
          <div className="rounded-xl border border-dashed border-app-line/60 p-8 text-center text-sm text-app-muted">
            لا توجد حركات مقبوضات أو مدفوعات مسجلة على هذا الصندوق في الفترة المحددة.
          </div>
        ) : (
          <div className="max-h-96 overflow-y-auto rounded-xl border border-app-line/40 sidebar-scrollbar">
            <table className="w-full text-right text-xs">
              <thead className="sticky top-0 bg-app-panel text-app-muted border-b border-app-line/50">
                <tr>
                  <th className="p-2.5">التاريخ</th>
                  <th className="p-2.5">رقم السند</th>
                  <th className="p-2.5">نوع السند</th>
                  <th className="p-2.5">البيان / الشرح</th>
                  <th className="p-2.5 text-left">وارد / قبض (+)</th>
                  <th className="p-2.5 text-left">صادر / صرف (-)</th>
                  <th className="p-2.5 text-left">الرصيد التراكمي</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-app-line/20 bg-app-card-soft/20 text-app-text">
                {movements.map((row, idx) => {
                  const amountIn = Number(row.in || row.receipt || (row.type === "RV" ? row.amount : 0));
                  const amountOut = Number(row.out || row.payment || (row.type === "PV" ? row.amount : 0));

                  return (
                    <tr key={row.id || idx} className="hover:bg-app-line/10 transition-colors">
                      <td className="p-2.5 whitespace-nowrap text-app-muted font-mono">{row.date}</td>
                      <td className="p-2.5 whitespace-nowrap font-mono font-medium text-app-yellow">
                        {row.number || row.journal_number || `#${row.journal_id || row.id}`}
                      </td>
                      <td className="p-2.5">
                        <span className="rounded bg-app-panel px-2 py-0.5 font-mono text-[10px] font-semibold text-app-text">
                          {row.type || "JV"}
                        </span>
                      </td>
                      <td className="p-2.5 text-app-text max-w-xs truncate" title={row.description || row.memo}>
                        {row.description || row.memo || "-"}
                      </td>
                      <td className="p-2.5 text-left font-mono text-emerald-400 font-medium">
                        {amountIn > 0 ? `+ ${currencySymbol} ${amountIn.toLocaleString()}` : "-"}
                      </td>
                      <td className="p-2.5 text-left font-mono text-rose-400 font-medium">
                        {amountOut > 0 ? `- ${currencySymbol} ${amountOut.toLocaleString()}` : "-"}
                      </td>
                      <td className="p-2.5 text-left font-mono font-bold text-app-text">
                        {row.running_balance !== undefined ? `${currencySymbol} ${Number(row.running_balance).toLocaleString()}` : "-"}
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
