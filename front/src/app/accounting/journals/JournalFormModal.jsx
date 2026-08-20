"use client";

import { useEffect, useState } from "react";
import Modal from "@/components/ui/Modal";
import Button from "@/components/ui/Button";
import { Field, TextAreaField } from "@/components/forms/FormControls";
import { PlusIcon, TrashIcon } from "@/components/icons/Icons";

const emptyLine = {
  account_id: "",
  debit_usd: "",
  credit_usd: "",
  debit_syp: "",
  credit_syp: "",
  memo: "",
};

export default function JournalFormModal({
  isOpen,
  onClose,
  accounts = [],
  safes = [],
  onSave,
  isLoading,
  errors = {},
}) {
  const [formData, setFormData] = useState({
    type: "JV",
    date: new Date().toISOString().split("T")[0],
    description: "",
    safe_id: "",
    exchange_rate: 1.0,
    notes: "",
    lines: [{ ...emptyLine }, { ...emptyLine }],
  });

  useEffect(() => {
    if (isOpen) {
      setFormData({
        type: "JV",
        date: new Date().toISOString().split("T")[0],
        description: "",
        safe_id: "",
        exchange_rate: 1.0,
        notes: "",
        lines: [{ ...emptyLine }, { ...emptyLine }],
      });
    }
  }, [isOpen]);

  const handleHeaderChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
  };

  const handleLineChange = (index, field, value) => {
    setFormData((prev) => {
      const nextLines = [...prev.lines];
      nextLines[index] = { ...nextLines[index], [field]: value };
      return { ...prev, lines: nextLines };
    });
  };

  const addLine = () => {
    setFormData((prev) => ({ ...prev, lines: [...prev.lines, { ...emptyLine }] }));
  };

  const removeLine = (index) => {
    if (formData.lines.length <= 2) return;
    setFormData((prev) => ({
      ...prev,
      lines: prev.lines.filter((_, i) => i !== index),
    }));
  };

  // Calculations for balance check
  const totals = formData.lines.reduce(
    (acc, line) => {
      acc.debitUsd += Number(line.debit_usd) || 0;
      acc.creditUsd += Number(line.credit_usd) || 0;
      acc.debitSyp += Number(line.debit_syp) || 0;
      acc.creditSyp += Number(line.credit_syp) || 0;
      return acc;
    },
    { debitUsd: 0, creditUsd: 0, debitSyp: 0, creditSyp: 0 }
  );

  const diffUsd = totals.debitUsd - totals.creditUsd;
  const diffSyp = totals.debitSyp - totals.creditSyp;
  const isBalancedUsd = Math.abs(diffUsd) < 0.001;
  const isBalancedSyp = Math.abs(diffSyp) < 0.001;
  const isFullyBalanced = isBalancedUsd && isBalancedSyp && (totals.debitUsd > 0 || totals.debitSyp > 0);

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!isFullyBalanced) return;

    const payload = {
      type: formData.type,
      date: formData.date,
      description: formData.description.trim(),
      safe_id: formData.safe_id ? Number(formData.safe_id) : null,
      exchange_rate: Number(formData.exchange_rate) || 1.0,
      notes: formData.notes?.trim() || null,
      lines: formData.lines.map((l) => ({
        account_id: Number(l.account_id),
        debit_usd: Number(l.debit_usd) || 0,
        credit_usd: Number(l.credit_usd) || 0,
        debit_syp: Number(l.debit_syp) || 0,
        credit_syp: Number(l.credit_syp) || 0,
        memo: l.memo?.trim() || null,
      })),
    };

    await onSave(payload);
  };

  return (
    <Modal
      open={isOpen}
      onClose={onClose}
      title="إنشاء سند قيد محاسبي جديد (مسودة)"
      size="xl"
    >
      <form onSubmit={handleSubmit} className="space-y-4" dir="rtl">
        {/* Header Fields */}
        <div className="grid grid-cols-1 md:grid-cols-4 gap-3 rounded-xl border border-app-line/40 bg-app-card-soft/40 p-4">
          <div>
            <label className="mb-1.5 block text-xs font-medium text-app-muted-light">
              نوع السند *
            </label>
            <select
              name="type"
              value={formData.type}
              onChange={handleHeaderChange}
              className="h-11 w-full rounded-xl border border-app-line bg-app-card-soft px-3 text-sm text-app-text outline-none focus:border-app-yellow"
              required
            >
              <option value="JV">قيد يومية عام (JV)</option>
              <option value="RV">سند قبض / وارد (RV)</option>
              <option value="PV">سند صرف / صادر (PV)</option>
            </select>
          </div>

          <Field
            label="تاريخ السند *"
            type="date"
            name="date"
            value={formData.date}
            onChange={handleHeaderChange}
            required
          />

          <div>
            <label className="mb-1.5 block text-xs font-medium text-app-muted-light">
              الصندوق المالي المرتبط (إن وجد)
            </label>
            <select
              name="safe_id"
              value={formData.safe_id}
              onChange={handleHeaderChange}
              className="h-11 w-full rounded-xl border border-app-line bg-app-card-soft px-3 text-sm text-app-text outline-none focus:border-app-yellow"
            >
              <option value="">-- بدون صندوق مباشر --</option>
              {safes.map((s) => (
                <option key={s.id} value={s.id}>
                  {s.name} ({s.currency})
                </option>
              ))}
            </select>
          </div>

          <Field
            label="سعر الصرف (SYP / USD)"
            type="number"
            name="exchange_rate"
            value={formData.exchange_rate}
            onChange={handleHeaderChange}
            step="any"
          />

          <div className="md:col-span-4">
            <Field
              label="البيان العام للسند (شرح الحركة) *"
              name="description"
              value={formData.description}
              onChange={handleHeaderChange}
              placeholder="مثال: إثبات مصاريف صيانة أو إيراد خدمات رياضية..."
              error={errors.description}
              required
            />
          </div>
        </div>

        {/* Lines Table */}
        <div className="space-y-2">
          <div className="flex items-center justify-between">
            <h3 className="text-sm font-bold text-app-text">بنود وأسطر القيد المزدوج</h3>
            <button
              type="button"
              onClick={addLine}
              className="flex items-center gap-1 text-xs text-app-yellow hover:underline"
            >
              <PlusIcon className="size-3.5" />
              <span>إضافة سطر قيد</span>
            </button>
          </div>

          <div className="overflow-x-auto rounded-xl border border-app-line/40">
            <table className="w-full text-right text-xs">
              <thead className="bg-app-panel text-app-muted border-b border-app-line/40">
                <tr>
                  <th className="p-2.5 w-48">الحساب المحاسبي *</th>
                  <th className="p-2.5 w-24 text-left">مدين (USD)</th>
                  <th className="p-2.5 w-24 text-left">دائن (USD)</th>
                  <th className="p-2.5 w-28 text-left">مدين (SYP)</th>
                  <th className="p-2.5 w-28 text-left">دائن (SYP)</th>
                  <th className="p-2.5">شرح البند (Memo)</th>
                  <th className="p-2.5 w-8"></th>
                </tr>
              </thead>
              <tbody className="divide-y divide-app-line/20 bg-app-card-soft/30">
                {formData.lines.map((line, idx) => (
                  <tr key={idx} className="hover:bg-app-line/10">
                    <td className="p-2">
                      <select
                        value={line.account_id}
                        onChange={(e) => handleLineChange(idx, "account_id", e.target.value)}
                        className="w-full rounded-lg border border-app-line bg-app-card px-2 py-1.5 text-xs text-app-text outline-none focus:border-app-yellow"
                        required
                      >
                        <option value="" className="bg-[#1a1b22] text-[#efefef]">-- اختر الحساب --</option>
                        {accounts
                          .filter((acc) => acc.allow_manual_entry !== false && acc.is_active !== false)
                          .map((acc) => (
                            <option key={acc.id} value={acc.id} className="bg-[#1a1b22] text-[#efefef]">
                              {acc.code} - {acc.name}
                            </option>
                          ))}
                      </select>
                    </td>

                    <td className="p-2">
                      <input
                        type="number"
                        step="any"
                        placeholder="0.00"
                        value={line.debit_usd}
                        onChange={(e) => handleLineChange(idx, "debit_usd", e.target.value)}
                        className="w-full rounded-lg border border-app-line bg-app-card px-2 py-1.5 text-left font-mono text-xs text-emerald-400 outline-none focus:border-app-yellow"
                      />
                    </td>

                    <td className="p-2">
                      <input
                        type="number"
                        step="any"
                        placeholder="0.00"
                        value={line.credit_usd}
                        onChange={(e) => handleLineChange(idx, "credit_usd", e.target.value)}
                        className="w-full rounded-lg border border-app-line bg-app-card px-2 py-1.5 text-left font-mono text-rose-400 outline-none focus:border-app-yellow"
                      />
                    </td>

                    <td className="p-2">
                      <input
                        type="number"
                        step="any"
                        placeholder="0"
                        value={line.debit_syp}
                        onChange={(e) => handleLineChange(idx, "debit_syp", e.target.value)}
                        className="w-full rounded-lg border border-app-line bg-app-card px-2 py-1.5 text-left font-mono text-emerald-400 outline-none focus:border-app-yellow"
                      />
                    </td>

                    <td className="p-2">
                      <input
                        type="number"
                        step="any"
                        placeholder="0"
                        value={line.credit_syp}
                        onChange={(e) => handleLineChange(idx, "credit_syp", e.target.value)}
                        className="w-full rounded-lg border border-app-line bg-app-card px-2 py-1.5 text-left font-mono text-rose-400 outline-none focus:border-app-yellow"
                      />
                    </td>

                    <td className="p-2">
                      <input
                        type="text"
                        placeholder="ملاحظات السطر..."
                        value={line.memo}
                        onChange={(e) => handleLineChange(idx, "memo", e.target.value)}
                        className="w-full rounded-lg border border-app-line bg-app-card px-2 py-1.5 text-xs text-app-text outline-none focus:border-app-yellow"
                      />
                    </td>

                    <td className="p-2 text-center">
                      <button
                        type="button"
                        onClick={() => removeLine(idx)}
                        disabled={formData.lines.length <= 2}
                        className="text-app-muted hover:text-rose-400 disabled:opacity-30"
                      >
                        <TrashIcon className="size-4" />
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
              <tfoot className="bg-app-panel font-bold border-t border-app-line/40">
                <tr>
                  <td className="p-2.5 text-app-muted">المجموع الإجمالي:</td>
                  <td className="p-2.5 text-left font-mono text-emerald-400">
                    ${totals.debitUsd.toLocaleString()}
                  </td>
                  <td className="p-2.5 text-left font-mono text-rose-400">
                    ${totals.creditUsd.toLocaleString()}
                  </td>
                  <td className="p-2.5 text-left font-mono text-emerald-400">
                    {totals.debitSyp.toLocaleString()}
                  </td>
                  <td className="p-2.5 text-left font-mono text-rose-400">
                    {totals.creditSyp.toLocaleString()}
                  </td>
                  <td colSpan={2} className="p-2.5 text-left">
                    {isFullyBalanced ? (
                      <span className="rounded bg-emerald-500/10 px-2 py-0.5 text-emerald-400 text-[11px] border border-emerald-500/20">
                        ✓ القيد متوازن
                      </span>
                    ) : (
                      <span className="rounded bg-rose-500/10 px-2 py-0.5 text-rose-400 text-[11px] border border-rose-500/20">
                        ✕ القيد غير متوازن
                      </span>
                    )}
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        <TextAreaField
          label="ملاحظات السند العامة"
          name="notes"
          value={formData.notes}
          onChange={handleHeaderChange}
          placeholder="تفاصيل إضافية عن الفاتورة أو المستند المؤيد..."
          rows={2}
        />

        <div className="flex justify-end gap-3 pt-3 border-t border-app-line/30">
          <Button variant="secondary" type="button" onClick={onClose} disabled={isLoading}>
            إلغاء
          </Button>
          <Button variant="primary" type="submit" disabled={isLoading || !isFullyBalanced}>
            {isLoading ? "جاري الحفظ..." : "حفظ السند كمسودة"}
          </Button>
        </div>
      </form>
    </Modal>
  );
}
