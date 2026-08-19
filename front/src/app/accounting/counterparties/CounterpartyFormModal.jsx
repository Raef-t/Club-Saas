"use client";

import { useEffect, useState } from "react";
import Modal from "@/components/ui/Modal";
import Button from "@/components/ui/Button";
import { Field, TextAreaField } from "@/components/forms/FormControls";
import { COUNTERPARTY_TYPES } from "./useCounterparties";

export default function CounterpartyFormModal({
  isOpen,
  onClose,
  counterparty,
  onSave,
  isLoading,
  errors = {},
}) {
  const [formData, setFormData] = useState({
    name: "",
    type: "supplier",
    phone: "",
    email: "",
    tax_number: "",
    is_active: true,
    notes: "",
  });

  useEffect(() => {
    if (counterparty) {
      setFormData({
        name: counterparty.name || "",
        type: counterparty.type || "supplier",
        phone: counterparty.phone || "",
        email: counterparty.email || "",
        tax_number: counterparty.tax_number || "",
        is_active: counterparty.is_active ?? true,
        notes: counterparty.notes || "",
      });
    } else {
      setFormData({
        name: "",
        type: "supplier",
        phone: "",
        email: "",
        tax_number: "",
        is_active: true,
        notes: "",
      });
    }
  }, [counterparty, isOpen]);

  const handleChange = (e) => {
    const { name, value, type, checked } = e.target;
    setFormData((prev) => ({
      ...prev,
      [name]: type === "checkbox" ? checked : value,
    }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    const payload = {
      ...formData,
      name: formData.name.trim(),
      phone: formData.phone?.trim() || null,
      email: formData.email?.trim() || null,
      tax_number: formData.tax_number?.trim() || null,
    };
    await onSave(payload);
  };

  return (
    <Modal
      open={isOpen}
      onClose={onClose}
      title={counterparty?.id ? `تعديل الطرف: ${counterparty.name}` : "تسجيل طرف / ذمة جديدة"}
      size="md"
    >
      <form onSubmit={handleSubmit} className="space-y-4" dir="rtl">
        <Field
          label="اسم الجهة / الشخص *"
          name="name"
          value={formData.name}
          onChange={handleChange}
          placeholder="مثال: شركة النور للأجهزة الرياضية، المورد أحمد..."
          error={errors.name}
          required
        />

        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label className="mb-1.5 block text-xs font-medium text-app-muted-light">
              طبيعة وتصنيف الطرف *
            </label>
            <select
              name="type"
              value={formData.type}
              onChange={handleChange}
              className="h-11 w-full rounded-xl border border-app-line bg-app-card-soft px-3 text-sm text-app-text outline-none focus:border-app-yellow"
              required
            >
              {Object.entries(COUNTERPARTY_TYPES).map(([key, item]) => (
                <option key={key} value={key}>
                  {item.label}
                </option>
              ))}
            </select>
          </div>

          <Field
            label="رقم الهاتف للتواصل"
            name="phone"
            value={formData.phone}
            onChange={handleChange}
            placeholder="09xxxxxxxx"
            error={errors.phone}
          />

          <Field
            label="البريد الإلكتروني"
            type="email"
            name="email"
            value={formData.email}
            onChange={handleChange}
            placeholder="example@mail.com"
            error={errors.email}
          />

          <Field
            label="الرقم الضريبي / السجل التجاري"
            name="tax_number"
            value={formData.tax_number}
            onChange={handleChange}
            placeholder="اختياري..."
          />
        </div>

        <div className="pt-1">
          <label className="flex items-center gap-2.5 cursor-pointer rounded-xl border border-app-line/40 bg-app-card-soft/50 p-3">
            <input
              type="checkbox"
              name="is_active"
              checked={formData.is_active}
              onChange={handleChange}
              className="size-4 rounded accent-app-yellow"
            />
            <div className="text-xs">
              <span className="font-semibold text-app-text block">طرف نشط</span>
              <span className="text-app-muted">يسمح باختياره في القيود وسندات الصرف والقبض</span>
            </div>
          </label>
        </div>

        <TextAreaField
          label="ملاحظات أو تفاصيل التعامل"
          name="notes"
          value={formData.notes}
          onChange={handleChange}
          placeholder="شروط الدفع، موقع المورد، أو معلومات أخرى..."
          rows={2}
        />

        <div className="flex justify-end gap-3 pt-3 border-t border-app-line/30">
          <Button variant="secondary" type="button" onClick={onClose} disabled={isLoading}>
            إلغاء
          </Button>
          <Button variant="primary" type="submit" disabled={isLoading}>
            {isLoading ? "جاري الحفظ..." : counterparty?.id ? "حفظ التعديلات" : "إضافة الطرف"}
          </Button>
        </div>
      </form>
    </Modal>
  );
}
