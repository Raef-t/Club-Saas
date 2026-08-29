"use client";

import { useEffect, useState } from "react";
import Modal from "@/components/ui/Modal";
import Button from "@/components/ui/Button";
import { Field, TextAreaField } from "@/components/forms/FormControls";

export default function ExpenseFormModal({
  isOpen,
  onClose,
  accounts = [],
  safes = [],
  counterparties = [],
  onSave,
  isLoading,
  errors = {},
}) {
  const [formData, setFormData] = useState({
    date: new Date().toISOString().split("T")[0],
    expense_account_id: "",
    safe_id: "",
    currency: "USD",
    amount: "",
    counterparty_id: "",
    description: "",
    notes: "",
  });

  const safeAccountsList = Array.isArray(accounts) ? accounts : [];
  const safeSafesList = Array.isArray(safes) ? safes : [];

  // Filter accounts for expenses (type === 'expense' or code starts with 5)
  const expenseAccounts = safeAccountsList.filter(
    (a) => a.type === "expense" || a.code?.startsWith("5")
  );

  useEffect(() => {
    if (isOpen) {
      const defaultSafe = safeSafesList.find((s) => s.is_default) || safeSafesList[0];
      setFormData({
        date: new Date().toISOString().split("T")[0],
        expense_account_id: expenseAccounts[0]?.id ? String(expenseAccounts[0].id) : "",
        safe_id: defaultSafe?.id ? String(defaultSafe.id) : "",
        currency: defaultSafe?.currency || "USD",
        amount: "",
        counterparty_id: "",
        description: "",
        notes: "",
      });
    }
  }, [isOpen, safeSafesList, expenseAccounts.length]);


  const handleChange = (e) => {
    const { name, value } = e.target;
    if (name === "safe_id") {
      const selectedSafe = safes.find((s) => String(s.id) === String(value));
      setFormData((prev) => ({
        ...prev,
        safe_id: value,
        currency: selectedSafe?.currency || prev.currency,
      }));
    } else {
      setFormData((prev) => ({ ...prev, [name]: value }));
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    const safe = safes.find((s) => String(s.id) === String(formData.safe_id));
    if (!safe?.account_id && !safe?.account?.id) {
      alert("الصندوق المالي المختار غير مربوط بحساب محاسبي!");
      return;
    }

    const safeAccountId = safe.account_id || safe.account.id;
    const amount = Number(formData.amount) || 0;
    const isUsd = formData.currency === "USD";

    // Auto-generate balanced double-entry voucher for the expense
    const payload = {
      type: "PV",
      date: formData.date,
      safe_id: Number(formData.safe_id),
      branch_id: safe.branch_id || null,
      description: formData.description.trim(),
      counterparty_id: formData.counterparty_id ? Number(formData.counterparty_id) : null,
      notes: formData.notes?.trim() || null,
      lines: [
        {
          account_id: Number(formData.expense_account_id),
          debit_usd: isUsd ? amount : 0,
          credit_usd: 0,
          debit_syp: !isUsd ? amount : 0,
          credit_syp: 0,
          memo: `صرف مصروف: ${formData.description.trim()}`,
        },
        {
          account_id: Number(safeAccountId),
          debit_usd: 0,
          credit_usd: isUsd ? amount : 0,
          debit_syp: 0,
          credit_syp: !isUsd ? amount : 0,
          memo: `صرف من الصندوق: ${safe.name}`,
        },
      ],
    };

    await onSave(payload);
  };

  return (
    <Modal
      open={isOpen}
      onClose={onClose}
      title="تسجيل سند صرف / مصروف جديد"
      size="lg"
    >
      <form onSubmit={handleSubmit} className="space-y-4" dir="rtl">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <Field
            label="تاريخ الصرف *"
            type="date"
            name="date"
            value={formData.date}
            onChange={handleChange}
            required
          />

          <div>
            <label className="mb-1.5 block text-xs font-medium text-app-muted-light">
              الصندوق المالي المنصرف منه *
            </label>
            <select
              name="safe_id"
              value={formData.safe_id}
              onChange={handleChange}
              className="h-11 w-full rounded-xl border border-app-line bg-app-card-soft px-3 text-sm text-app-text outline-none focus:border-app-yellow"
              required
            >
              <option value="">-- اختر الصندوق / الخزينة --</option>
              {safes.map((s) => (
                <option key={s.id} value={s.id}>
                  {s.name} ({s.currency})
                </option>
              ))}
            </select>
            {errors.safe_id && <p className="mt-1 text-xs text-rose-500">{errors.safe_id}</p>}
          </div>

          <div>
            <label className="mb-1.5 block text-xs font-medium text-app-muted-light">
              بند / حساب المصروف (من الدليل) *
            </label>
            <select
              name="expense_account_id"
              value={formData.expense_account_id}
              onChange={handleChange}
              className="h-11 w-full rounded-xl border border-app-line bg-app-card-soft px-3 text-sm text-app-text outline-none focus:border-app-yellow"
              required
            >
              <option value="">-- اختر بند المصروف --</option>
              {expenseAccounts.map((acc) => (
                <option key={acc.id} value={acc.id}>
                  {acc.code} - {acc.name}
                </option>
              ))}
            </select>
            {errors.expense_account_id && (
              <p className="mt-1 text-xs text-rose-500">{errors.expense_account_id}</p>
            )}
          </div>

          <div>
            <label className="mb-1.5 block text-xs font-medium text-app-muted-light">
              المبلغ المنصرف ({formData.currency}) *
            </label>
            <input
              type="number"
              step="any"
              name="amount"
              value={formData.amount}
              onChange={handleChange}
              placeholder="0.00"
              className="h-11 w-full rounded-xl border border-app-line bg-app-card-soft px-3 text-sm font-mono text-rose-400 font-bold outline-none focus:border-app-yellow"
              required
            />
            {errors.amount && <p className="mt-1 text-xs text-rose-500">{errors.amount}</p>}
          </div>

          <div className="md:col-span-2">
            <Field
              label="البيان / سبب المصروف *"
              name="description"
              value={formData.description}
              onChange={handleChange}
              placeholder="مثال: فاتورة كهرباء شهر حزيران، صيانة جهاز مشي، ضيافة..."
              error={errors.description}
              required
            />
          </div>

          <div>
            <label className="mb-1.5 block text-xs font-medium text-app-muted-light">
              الطرف المستلم / المورد (اختياري)
            </label>
            <select
              name="counterparty_id"
              value={formData.counterparty_id}
              onChange={handleChange}
              className="h-11 w-full rounded-xl border border-app-line bg-app-card-soft px-3 text-sm text-app-text outline-none focus:border-app-yellow"
            >
              <option value="">-- بدون تحديد جهة / طرف خارجي --</option>
              {counterparties.map((c) => (
                <option key={c.id} value={c.id}>
                  {c.name} ({c.type})
                </option>
              ))}
            </select>
          </div>

          <div>
            <Field
              label="ملاحظات أو رقم الفاتورة الورقية (اختياري)"
              name="notes"
              value={formData.notes}
              onChange={handleChange}
              placeholder="رقم الفاتورة أو المستند..."
              required={false}
            />
          </div>
        </div>

        <div className="flex justify-end gap-3 pt-3 border-t border-app-line/30">
          <Button variant="secondary" type="button" onClick={onClose} disabled={isLoading}>
            إلغاء
          </Button>
          <Button variant="primary" type="submit" disabled={isLoading || !formData.amount}>
            {isLoading ? "جاري الحفظ..." : "تسجيل سند الصرف"}
          </Button>
        </div>
      </form>
    </Modal>
  );
}
