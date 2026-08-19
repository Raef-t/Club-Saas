"use client";

import Modal from "@/components/ui/Modal";
import Button from "@/components/ui/Button";
import { JOURNAL_STATUSES, JOURNAL_TYPES } from "./useJournals";

export default function JournalDetailsModal({
  isOpen,
  onClose,
  journal,
  onOpenAction,
}) {
  if (!journal) return null;

  const typeInfo = JOURNAL_TYPES[journal.type] || { label: journal.type, badge: "bg-app-card" };
  const statusInfo = JOURNAL_STATUSES[journal.status] || { label: journal.status, color: "bg-app-card" };
  const entries = journal.entries || [];

  const totals = entries.reduce(
    (acc, entry) => {
      acc.debitUsd += Number(entry.debit_usd || 0);
      acc.creditUsd += Number(entry.credit_usd || 0);
      acc.debitSyp += Number(entry.debit_syp || 0);
      acc.creditSyp += Number(entry.credit_syp || 0);
      return acc;
    },
    { debitUsd: 0, creditUsd: 0, debitSyp: 0, creditSyp: 0 }
  );

  return (
    <Modal
      open={isOpen}
      onClose={onClose}
      title={`تفاصيل السند المحاسبي: ${journal.number || `#${journal.id}`}`}
      size="xl"
    >
      <div className="space-y-4" dir="rtl">
        {/* Info Grid */}
        <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 rounded-xl border border-app-line/40 bg-app-card-soft/40 p-4 text-xs">
          <div>
            <span className="text-app-muted block">نوع السند:</span>
            <span className={`inline-block mt-1 rounded-full px-2.5 py-0.5 border font-semibold ${typeInfo.badge}`}>
              {typeInfo.label}
            </span>
          </div>

          <div>
            <span className="text-app-muted block">حالة السند:</span>
            <span className={`inline-block mt-1 rounded-full px-2.5 py-0.5 border font-semibold ${statusInfo.color}`}>
              {statusInfo.label}
            </span>
          </div>

          <div>
            <span className="text-app-muted block">تاريخ السند:</span>
            <span className="font-mono font-bold text-app-text block mt-1">{journal.date}</span>
          </div>

          <div>
            <span className="text-app-muted block">الصندوق المرتبط:</span>
            <span className="font-medium text-app-text block mt-1">{journal.safe?.name || "بدون صندوق مباشر"}</span>
          </div>

          <div className="col-span-2 sm:col-span-4 border-t border-app-line/30 pt-2">
            <span className="text-app-muted block">البيان والشرح العام:</span>
            <p className="mt-1 font-medium text-app-text">{journal.description || "-"}</p>
          </div>

          {journal.notes && (
            <div className="col-span-2 sm:col-span-4 border-t border-app-line/30 pt-2">
              <span className="text-app-muted block">ملاحظات:</span>
              <p className="mt-1 text-app-muted-light">{journal.notes}</p>
            </div>
          )}

          {journal.cancellation_reason && (
            <div className="col-span-2 sm:col-span-4 rounded-lg bg-rose-500/10 p-2.5 border border-rose-500/20 text-rose-300">
              <strong>سبب الإلغاء:</strong> {journal.cancellation_reason}
            </div>
          )}
        </div>

        {/* Entries Table */}
        <div className="overflow-x-auto rounded-xl border border-app-line/40">
          <table className="w-full text-right text-xs">
            <thead className="bg-app-panel text-app-muted border-b border-app-line/40">
              <tr>
                <th className="p-2.5">رمز الحساب</th>
                <th className="p-2.5">اسم الحساب المحاسبي</th>
                <th className="p-2.5 text-left">مدين (USD)</th>
                <th className="p-2.5 text-left">دائن (USD)</th>
                <th className="p-2.5 text-left">مدين (SYP)</th>
                <th className="p-2.5 text-left">دائن (SYP)</th>
                <th className="p-2.5">البيان (Memo)</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-app-line/20 bg-app-card-soft/20 text-app-text">
              {entries.map((entry, idx) => (
                <tr key={entry.id || idx} className="hover:bg-app-line/10">
                  <td className="p-2.5 font-mono font-bold text-app-yellow">{entry.account?.code || "-"}</td>
                  <td className="p-2.5 font-medium">{entry.account?.name || "-"}</td>
                  <td className="p-2.5 text-left font-mono text-emerald-400 font-medium">
                    {Number(entry.debit_usd) > 0 ? `$${Number(entry.debit_usd).toLocaleString()}` : "-"}
                  </td>
                  <td className="p-2.5 text-left font-mono text-rose-400 font-medium">
                    {Number(entry.credit_usd) > 0 ? `$${Number(entry.credit_usd).toLocaleString()}` : "-"}
                  </td>
                  <td className="p-2.5 text-left font-mono text-emerald-400">
                    {Number(entry.debit_syp) > 0 ? `${Number(entry.debit_syp).toLocaleString()}` : "-"}
                  </td>
                  <td className="p-2.5 text-left font-mono text-rose-400">
                    {Number(entry.credit_syp) > 0 ? `${Number(entry.credit_syp).toLocaleString()}` : "-"}
                  </td>
                  <td className="p-2.5 text-app-muted">{entry.memo || "-"}</td>
                </tr>
              ))}
            </tbody>
            <tfoot className="bg-app-panel font-bold border-t border-app-line/40">
              <tr>
                <td colSpan={2} className="p-2.5 text-app-muted">المجموع الإجمالي:</td>
                <td className="p-2.5 text-left font-mono text-emerald-400">${totals.debitUsd.toLocaleString()}</td>
                <td className="p-2.5 text-left font-mono text-rose-400">${totals.creditUsd.toLocaleString()}</td>
                <td className="p-2.5 text-left font-mono text-emerald-400">{totals.debitSyp.toLocaleString()}</td>
                <td className="p-2.5 text-left font-mono text-rose-400">{totals.creditSyp.toLocaleString()}</td>
                <td></td>
              </tr>
            </tfoot>
          </table>
        </div>

        {/* Footer Actions */}
        <div className="flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-app-line/30">
          <div className="flex items-center gap-2">
            {journal.status === "draft" && (
              <>
                <Button variant="primary" onClick={() => onOpenAction("post", journal)}>
                  ترحيل السند (Post)
                </Button>
                <Button variant="danger" onClick={() => onOpenAction("cancel", journal)}>
                  إلغاء السند
                </Button>
              </>
            )}
            {journal.status === "posted" && (
              <Button variant="warning" onClick={() => onOpenAction("reverse", journal)}>
                عكس السند (Reverse Entry)
              </Button>
            )}
          </div>

          <Button variant="secondary" onClick={onClose}>
            إغلاق
          </Button>
        </div>
      </div>
    </Modal>
  );
}
