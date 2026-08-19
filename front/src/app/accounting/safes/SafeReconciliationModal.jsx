"use client";

import { useEffect, useState } from "react";
import Modal from "@/components/ui/Modal";
import Button from "@/components/ui/Button";
import { Field, TextAreaField } from "@/components/forms/FormControls";

export default function SafeReconciliationModal({
  isOpen,
  onClose,
  safe,
  onSave,
  isLoading,
}) {
  if (!safe) return null;

  const [formData, setFormData] = useState({

    date: new Date().toISOString().split("T")[0],
    actual_balance: "",
    notes: "",
  });

  useEffect(() => {
    if (isOpen) {
      setFormData({
        date: new Date().toISOString().split("T")[0],
        actual_balance: "",
        notes: "",
      });
    }
  }, [isOpen]);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    const payload = {
      safe_id: safe.id,
      date: formData.date,
      actual_balance: Number(formData.actual_balance) || 0,
      notes: formData.notes?.trim() || null,
    };
    await onSave(payload);
  };

  const currencySymbol = safe?.currency === "USD" ? "$" : "ل.س";

  return (
    <Modal
      open={isOpen}
      onClose={onClose}
      title={`مطابقة وتسوية الصندوق: ${safe?.name}`}
      size="md"
    >
      <form onSubmit={handleSubmit} className="space-y-4" dir="rtl">
        <div className="rounded-xl border border-app-line/40 bg-app-card-soft/40 p-4">
          <div className="flex items-center justify-between text-xs text-app-muted">
            <span>العملة: <strong className="text-app-text font-mono">{safe?.currency}</strong></span>
            <span>الفرع: <strong className="text-app-text">{safe?.branch?.name || "-"}</strong></span>
          </div>
        </div>

        <Field
          label="تاريخ المطابقة والجرد *"
          type="date"
          name="date"
          value={formData.date}
          onChange={handleChange}
          required
        />

        <Field
          label={`الرصيد الفعلي الموجود بالصندوق (${currencySymbol}) *`}
          type="number"
          name="actual_balance"
          value={formData.actual_balance}
          onChange={handleChange}
          placeholder="أدخل المبلغ بعد الجرد النقدي الفعلي..."
          step="any"
          required
        />

        <TextAreaField
          label="ملاحظات التسوية وأسباب الفروقات (إن وجدت)"
          name="notes"
          value={formData.notes}
          onChange={handleChange}
          placeholder="تفاصيل العجز أو الفائض أو ملاحظات إقفال الوردية..."
          rows={3}
        />

        <div className="flex justify-end gap-3 pt-3 border-t border-app-line/30">
          <Button variant="secondary" type="button" onClick={onClose} disabled={isLoading}>
            إلغاء
          </Button>
          <Button variant="primary" type="submit" disabled={isLoading}>
            {isLoading ? "جاري الحفظ..." : "اعتماد التسوية والمطابقة"}
          </Button>
        </div>
      </form>
    </Modal>
  );
}
