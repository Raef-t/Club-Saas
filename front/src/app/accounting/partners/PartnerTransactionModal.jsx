"use client";

import { useEffect, useState } from "react";
import Modal from "@/components/ui/Modal";
import Button from "@/components/ui/Button";
import { Field, TextAreaField } from "@/components/forms/FormControls";

export default function PartnerTransactionModal({
  isOpen,
  onClose,
  type,
  partner,
  safes = [],
  onExecute,
  isLoading,
}) {
  const [formData, setFormData] = useState({
    safe_id: "",
    amount: "",
    currency: "USD",
    date: new Date().toISOString().split("T")[0],
    notes: "",
  });

  const isDeposit = type === "deposit";

  useEffect(() => {
    if (isOpen) {
      const defaultSafe = safes.find((s) => s.is_default) || safes[0];
      setFormData({
        safe_id: defaultSafe?.id ? String(defaultSafe.id) : "",
        amount: "",
        currency: defaultSafe?.currency || "USD",
        date: new Date().toISOString().split("T")[0],
        notes: "",
      });
    }
  }, [isOpen, safes]);

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
    const payload = {
      safe_id: Number(formData.safe_id),
      amount: Number(formData.amount) || 0,
      currency: formData.currency,
      date: formData.date,
      notes: formData.notes?.trim() || null,
    };
    await onExecute(payload);
  };

  if (!partner) return null;

  return (
    <Modal
      open={isOpen}
      onClose={onClose}
      title={isDeposit ? `إيداع رأس مال: ${partner.name}` : `سحب مسحوبات شخصية: ${partner.name}`}
      size="md"
    >
      <form onSubmit={handleSubmit} className="space-y-4" dir="rtl">
        <div className="rounded-xl border border-app-line/40 bg-app-card-soft/40 p-3.5 text-xs">
          <span className="text-app-muted block">حساب الشريك المتأثر:</span>
          <span className="font-semibold text-app-text">
            {isDeposit ? `رأس المال: ${partner.capital_account?.name || "حساب رأس المال"}` : `المسحوبات: ${partner.drawings_account?.name || "حساب المسحوبات"}`}
          </span>
        </div>

        <div>
          <label className="mb-1.5 block text-xs font-medium text-app-muted-light">
            {isDeposit ? "الصندوق المودع به المبلغ *" : "الصندوق المنصرف منه المبلغ *"}
          </label>
          <select
            name="safe_id"
            value={formData.safe_id}
            onChange={handleChange}
            className="h-11 w-full rounded-xl border border-app-line bg-app-card-soft px-3 text-sm text-app-text outline-none focus:border-app-yellow"
            required
          >
            <option value="">-- اختر الصندوق المالي --</option>
            {safes.map((s) => (
              <option key={s.id} value={s.id}>
                {s.name} ({s.currency})
              </option>
            ))}
          </select>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label className="mb-1.5 block text-xs font-medium text-app-muted-light">
              المبلغ ({formData.currency}) *
            </label>
            <input
              type="number"
              step="any"
              name="amount"
              value={formData.amount}
              onChange={handleChange}
              placeholder="0.00"
              className={`h-11 w-full rounded-xl border border-app-line bg-app-card-soft px-3 text-sm font-mono font-bold outline-none focus:border-app-yellow ${
                isDeposit ? "text-emerald-400" : "text-rose-400"
              }`}
              required
            />
          </div>

          <Field
            label="تاريخ العملية *"
            type="date"
            name="date"
            value={formData.date}
            onChange={handleChange}
            required
          />
        </div>

        <TextAreaField
          label="البيان / ملاحظات السند"
          name="notes"
          value={formData.notes}
          onChange={handleChange}
          placeholder={isDeposit ? "مثال: دفعة زيادة رأس المال نقداً..." : "مثال: سحب أرباح أو مسحوبات شخصية..."}
          rows={2}
        />

        <div className="flex justify-end gap-3 pt-3 border-t border-app-line/30">
          <Button variant="secondary" type="button" onClick={onClose} disabled={isLoading}>
            إلغاء
          </Button>
          <Button
            variant={isDeposit ? "primary" : "warning"}
            type="submit"
            disabled={isLoading || !formData.amount}
          >
            {isLoading ? "جاري المعالجة..." : isDeposit ? "تأكيد الإيداع" : "تأكيد السحب"}
          </Button>
        </div>
      </form>
    </Modal>
  );
}
