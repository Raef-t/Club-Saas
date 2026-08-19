"use client";

import { useEffect, useState } from "react";
import Modal from "@/components/ui/Modal";
import Button from "@/components/ui/Button";
import { Field, TextAreaField } from "@/components/forms/FormControls";
import { ACCOUNT_TYPES, CURRENCY_OPTIONS } from "./useAccounts";

export default function AccountFormModal({
  isOpen,
  onClose,
  account,
  accounts = [],
  onSave,
  isLoading,
  errors = {},
}) {
  const [formData, setFormData] = useState({
    code: "",
    name: "",
    name_en: "",
    type: "asset",
    currency: "BOTH",
    parent_id: "",
    allow_manual_entry: true,
    is_active: true,
    notes: "",
  });

  useEffect(() => {
    if (account) {
      setFormData({
        code: account.code || "",
        name: account.name || "",
        name_en: account.name_en || "",
        type: account.type || "asset",
        currency: account.currency || "BOTH",
        parent_id: account.parent_id ? String(account.parent_id) : "",
        allow_manual_entry: account.allow_manual_entry ?? true,
        is_active: account.is_active ?? true,
        notes: account.notes || account.description || "",
      });
    } else {
      setFormData({
        code: "",
        name: "",
        name_en: "",
        type: "asset",
        currency: "BOTH",
        parent_id: "",
        allow_manual_entry: true,
        is_active: true,
        notes: "",
      });
    }
  }, [account, isOpen]);

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
      parent_id: formData.parent_id ? Number(formData.parent_id) : null,
      code: formData.code.trim(),
      name: formData.name.trim(),
      name_en: formData.name_en?.trim() || null,
    };
    await onSave(payload);
  };

  // Filter out the current account itself from potential parents
  const availableParents = accounts.filter((a) => !account?.id || a.id !== account.id);

  return (
    <Modal
      open={isOpen}
      onClose={onClose}
      title={account?.id ? `تعديل الحساب: ${account.name}` : "إضافة حساب جديد إلى الدليل"}
      size="lg"
    >
      <form onSubmit={handleSubmit} className="space-y-4" dir="rtl">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <Field
            label="رمز الحساب (الكود المحاسبي) *"
            name="code"
            value={formData.code}
            onChange={handleChange}
            placeholder="مثال: 1101"
            error={errors.code}
            required
          />

          <div>
            <label className="mb-1.5 block text-xs font-medium text-app-muted-light">
              الحساب الأب (المستوى الأعلى)
            </label>
            <select
              name="parent_id"
              value={formData.parent_id}
              onChange={handleChange}
              className="h-11 w-full rounded-xl border border-app-line bg-app-card-soft px-3 text-sm text-app-text outline-none focus:border-app-yellow"
            >
              <option value="">-- حساب رئيسي (بدون حساب أب) --</option>
              {availableParents.map((p) => (
                <option key={p.id} value={p.id}>
                  {p.code} - {p.name} ({ACCOUNT_TYPES[p.type]?.label || p.type})
                </option>
              ))}
            </select>
            {errors.parent_id && (
              <p className="mt-1 text-xs text-rose-500">{errors.parent_id}</p>
            )}
          </div>

          <Field
            label="اسم الحساب (بالعربية) *"
            name="name"
            value={formData.name}
            onChange={handleChange}
            placeholder="مثال: الصندوق الرئيسي"
            error={errors.name}
            required
          />

          <Field
            label="اسم الحساب (بالإنجليزية)"
            name="name_en"
            value={formData.name_en}
            onChange={handleChange}
            placeholder="e.g. Main Cashbox"
            error={errors.name_en}
          />

          <div>
            <label className="mb-1.5 block text-xs font-medium text-app-muted-light">
              طبيعة الحساب (التبويب المالي) *
            </label>
            <select
              name="type"
              value={formData.type}
              onChange={handleChange}
              className="h-11 w-full rounded-xl border border-app-line bg-app-card-soft px-3 text-sm text-app-text outline-none focus:border-app-yellow"
              required
            >
              {Object.entries(ACCOUNT_TYPES).map(([key, item]) => (
                <option key={key} value={key}>
                  {item.label}
                </option>
              ))}
            </select>
            {errors.type && <p className="mt-1 text-xs text-rose-500">{errors.type}</p>}
          </div>

          <div>
            <label className="mb-1.5 block text-xs font-medium text-app-muted-light">
              العملة المقبولة بالحساب *
            </label>
            <select
              name="currency"
              value={formData.currency}
              onChange={handleChange}
              className="h-11 w-full rounded-xl border border-app-line bg-app-card-soft px-3 text-sm text-app-text outline-none focus:border-app-yellow"
              required
            >
              {CURRENCY_OPTIONS.map((opt) => (
                <option key={opt.value} value={opt.value}>
                  {opt.label}
                </option>
              ))}
            </select>
            {errors.currency && (
              <p className="mt-1 text-xs text-rose-500">{errors.currency}</p>
            )}
          </div>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
          <label className="flex items-center gap-2.5 cursor-pointer rounded-xl border border-app-line/40 bg-app-card-soft/50 p-3">
            <input
              type="checkbox"
              name="allow_manual_entry"
              checked={formData.allow_manual_entry}
              onChange={handleChange}
              className="size-4 rounded accent-app-yellow"
            />
            <div className="text-xs">
              <span className="font-semibold text-app-text block">السماح بالقيد اليدوي</span>
              <span className="text-app-muted">يسمح باختيار الحساب في سندات القيد اليومية</span>
            </div>
          </label>

          <label className="flex items-center gap-2.5 cursor-pointer rounded-xl border border-app-line/40 bg-app-card-soft/50 p-3">
            <input
              type="checkbox"
              name="is_active"
              checked={formData.is_active}
              onChange={handleChange}
              className="size-4 rounded accent-app-yellow"
            />
            <div className="text-xs">
              <span className="font-semibold text-app-text block">حالة الحساب نشط</span>
              <span className="text-app-muted">يمكن استخدامه في العمليات المالية الجارية</span>
            </div>
          </label>
        </div>

        <TextAreaField
          label="شرح أو ملاحظات إضافية"
          name="notes"
          value={formData.notes}
          onChange={handleChange}
          placeholder="تفاصيل الغرض من الحساب أو توجيهات الاستخدام..."
          rows={3}
        />

        <div className="flex justify-end gap-3 pt-4 border-t border-app-line/30">
          <Button variant="secondary" type="button" onClick={onClose} disabled={isLoading}>
            إلغاء
          </Button>
          <Button variant="primary" type="submit" disabled={isLoading}>
            {isLoading ? "جاري الحفظ..." : account?.id ? "حفظ التعديلات" : "إضافة الحساب"}
          </Button>
        </div>
      </form>
    </Modal>
  );
}
