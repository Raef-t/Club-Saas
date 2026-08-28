"use client";

import { useEffect, useState } from "react";
import Modal from "@/components/ui/Modal";
import Button from "@/components/ui/Button";
import { Field, TextAreaField } from "@/components/forms/FormControls";

export default function SalaryPaymentFormModal({
  isOpen,
  onClose,
  staffList = [],
  safes = [],
  periods = [],
  onSave,
  isLoading,
  errors = {},
}) {
  const [formData, setFormData] = useState({
    staff_id: "",
    safe_id: "",
    period_id: "",
    amount: "",
    payment_date: new Date().toISOString().split("T")[0],
    payment_method: "cash",
    notes: "",
  });

  useEffect(() => {
    const safeSafesList = Array.isArray(safes) ? safes : [];
    const safePeriodsList = Array.isArray(periods) ? periods : [];
    const safeStaffList = Array.isArray(staffList) ? staffList : [];

    if (isOpen) {
      const defaultSafe = safeSafesList.find((s) => s.is_default) || safeSafesList[0];
      const openPeriod = safePeriodsList.find((p) => p.status === "open") || safePeriodsList[0];

      setFormData({
        staff_id: safeStaffList[0]?.id ? String(safeStaffList[0].id) : "",
        safe_id: defaultSafe?.id ? String(defaultSafe.id) : "",
        period_id: openPeriod?.id ? String(openPeriod.id) : "",
        amount: "",
        payment_date: new Date().toISOString().split("T")[0],
        payment_method: "cash",
        notes: "",
      });
    }
  }, [isOpen, safes, periods, staffList]);


  const handleChange = (e) => {
    const { name, value } = e.target;
    if (name === "staff_id") {
      const selectedStaff = staffList.find((s) => String(s.id) === String(value));
      setFormData((prev) => ({
        ...prev,
        staff_id: value,
        amount: selectedStaff?.base_salary ? String(selectedStaff.base_salary) : prev.amount,
      }));
    } else {
      setFormData((prev) => ({ ...prev, [name]: value }));
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    const payload = {
      staff_id: Number(formData.staff_id),
      safe_id: Number(formData.safe_id),
      period_id: formData.period_id ? Number(formData.period_id) : null,
      amount: Number(formData.amount) || 0,
      date: formData.payment_date,
      payment_date: formData.payment_date,
      payment_method: formData.payment_method,
      notes: formData.notes?.trim() || null,
    };
    await onSave(payload);
  };

  return (
    <Modal
      open={isOpen}
      onClose={onClose}
      title="صرف راتب موظف / مدرب"
      size="lg"
    >
      <form onSubmit={handleSubmit} className="space-y-4" dir="rtl">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label className="mb-1.5 block text-xs font-medium text-app-muted-light">
              الموظف / المدرب المستحق *
            </label>
            <select
              name="staff_id"
              value={formData.staff_id}
              onChange={handleChange}
              className="h-11 w-full rounded-xl border border-app-line bg-app-card-soft px-3 text-sm text-app-text outline-none focus:border-app-yellow"
              required
            >
              <option value="">-- اختر الكادر --</option>
              {staffList.map((s) => {
                const name = s.person?.full_name || `${s.person?.first_name || ""} ${s.person?.last_name || ""}`.trim() || s.name || `#${s.id}`;
                  const roleLabel = s.role === "coach" ? "مدرب" : s.role === "admin" ? "إداري" : "موظف";
                  const salaryLabel = Number(s.base_salary) > 0 ? ` — الراتب الأساسي: ${Number(s.base_salary).toLocaleString()}` : "";
                  return (
                    <option key={s.id} value={s.id}>
                      {name} ({roleLabel}){salaryLabel}
                    </option>
                  );
              })}
            </select>
            {errors.staff_id && <p className="mt-1 text-xs text-rose-500">{errors.staff_id}</p>}
          </div>

          <div>
            <label className="mb-1.5 block text-xs font-medium text-app-muted-light">
              الصندوق المالي للصرف *
            </label>
            <select
              name="safe_id"
              value={formData.safe_id}
              onChange={handleChange}
              className="h-11 w-full rounded-xl border border-app-line bg-app-card-soft px-3 text-sm text-app-text outline-none focus:border-app-yellow"
              required
            >
              <option value="">-- اختر الصندوق --</option>
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
              الفترة المالية المحاسبية
            </label>
            <select
              name="period_id"
              value={formData.period_id}
              onChange={handleChange}
              className="h-11 w-full rounded-xl border border-app-line bg-app-card-soft px-3 text-sm text-app-text outline-none focus:border-app-yellow"
            >
              <option value="">-- بدون فترة محددة --</option>
              {periods.map((p) => (
                <option key={p.id} value={p.id}>
                  {p.name} ({p.status})
                </option>
              ))}
            </select>
          </div>

          <div>
            <label className="mb-1.5 block text-xs font-medium text-app-muted-light">
              المبلغ المصروف *
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

          <Field
            label="تاريخ الصرف والدفع *"
            type="date"
            name="payment_date"
            value={formData.payment_date}
            onChange={handleChange}
            required
          />

          <div>
            <label className="mb-1.5 block text-xs font-medium text-app-muted-light">
              طريقة الدفع
            </label>
            <select
              name="payment_method"
              value={formData.payment_method}
              onChange={handleChange}
              className="h-11 w-full rounded-xl border border-app-line bg-app-card-soft px-3 text-sm text-app-text outline-none focus:border-app-yellow"
            >
              <option value="cash">نقداً (Cash)</option>
              <option value="bank">تحويل بنكي (Bank Transfer)</option>
              <option value="card">بطاقة (Card)</option>
            </select>
          </div>
        </div>

        <TextAreaField
          label="البيان / ملاحظات الصرف"
          name="notes"
          value={formData.notes}
          onChange={handleChange}
          placeholder="مثال: راتب شهر تموز مع حوافز الحضور..."
          rows={2}
        />

        <div className="flex justify-end gap-3 pt-3 border-t border-app-line/30">
          <Button variant="secondary" type="button" onClick={onClose} disabled={isLoading}>
            إلغاء
          </Button>
          <Button variant="primary" type="submit" disabled={isLoading || !formData.amount}>
            {isLoading ? "جاري الحفظ..." : "تأكيد صرف الراتب"}
          </Button>
        </div>
      </form>
    </Modal>
  );
}
