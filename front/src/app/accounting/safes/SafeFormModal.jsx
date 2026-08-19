"use client";

import { useEffect, useState } from "react";
import Modal from "@/components/ui/Modal";
import Button from "@/components/ui/Button";
import { Field, TextAreaField } from "@/components/forms/FormControls";

export default function SafeFormModal({
  isOpen,
  onClose,
  safe,
  accounts = [],
  branches = [],
  currentBranchId,
  onSave,
  isLoading,
  errors = {},
}) {
  const [formData, setFormData] = useState({
    name: "",
    currency: "USD",
    branch_id: "",
    account_id: "",
    is_default: false,
    is_active: true,
    notes: "",
  });

  useEffect(() => {
    const safeBranchesList = Array.isArray(branches) ? branches : [];
    if (safe) {
      setFormData({
        name: safe.name || "",
        currency: safe.currency || "USD",
        branch_id: safe.branch_id ? String(safe.branch_id) : "",
        account_id: safe.account_id ? String(safe.account_id) : safe.account?.id ? String(safe.account.id) : "",
        is_default: Boolean(safe.is_default),
        is_active: safe.is_active ?? true,
        notes: safe.notes || "",
      });
    } else {
      setFormData({
        name: "",
        currency: "USD",
        branch_id: currentBranchId && currentBranchId !== "all" ? String(currentBranchId) : safeBranchesList[0]?.id ? String(safeBranchesList[0].id) : "",
        account_id: "",
        is_default: false,
        is_active: true,
        notes: "",
      });
    }
  }, [safe, isOpen, currentBranchId, branches]);


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
      branch_id: formData.branch_id ? Number(formData.branch_id) : null,
      account_id: formData.account_id ? Number(formData.account_id) : null,
      name: formData.name.trim(),
    };
    await onSave(payload);
  };

  const safeAccountsList = Array.isArray(accounts) ? accounts : [];
  const safeBranchesList = Array.isArray(branches) ? branches : [];
  const assetAccounts = safeAccountsList.filter((a) => a.type === "asset" || a.code?.startsWith("1"));

  return (
    <Modal
      open={isOpen}
      onClose={onClose}
      title={safe?.id ? `تعديل الصندوق: ${safe.name}` : "إضافة صندوق مالي / خزينة جديدة"}
      size="lg"
    >
      <form onSubmit={handleSubmit} className="space-y-4" dir="rtl">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <Field
            label="اسم الصندوق / الخزينة *"
            name="name"
            value={formData.name}
            onChange={handleChange}
            placeholder="مثال: صندوق استقبال فرع دبي"
            error={errors.name}
            required
          />

          <div>
            <label className="mb-1.5 block text-xs font-medium text-app-muted-light">
              الفرع التابع له الصندوق *
            </label>
            <select
              name="branch_id"
              value={formData.branch_id}
              onChange={handleChange}
              className="h-11 w-full rounded-xl border border-app-line bg-app-card-soft px-3 text-sm text-app-text outline-none focus:border-app-yellow"
              required
            >
              <option value="">-- اختر الفرع --</option>
              {safeBranchesList.map((b) => (
                <option key={b.id} value={b.id}>
                  {b.name}
                </option>
              ))}
            </select>

            {errors.branch_id && (
              <p className="mt-1 text-xs text-rose-500">{errors.branch_id}</p>
            )}
          </div>

          <div>
            <label className="mb-1.5 block text-xs font-medium text-app-muted-light">
              العملة الرسمية للصندوق *
            </label>
            <select
              name="currency"
              value={formData.currency}
              onChange={handleChange}
              disabled={Boolean(safe?.id)} // Currency shouldn't change after creation if entries exist
              className="h-11 w-full rounded-xl border border-app-line bg-app-card-soft px-3 text-sm text-app-text outline-none focus:border-app-yellow disabled:opacity-60"
              required
            >
              <option value="USD">دولار أمريكي (USD)</option>
              <option value="SYP">ليرة سورية (SYP)</option>
            </select>
            {errors.currency && (
              <p className="mt-1 text-xs text-rose-500">{errors.currency}</p>
            )}
          </div>

          <div>
            <label className="mb-1.5 block text-xs font-medium text-app-muted-light">
              الحساب المحاسبي المقابل في الشجرة *
            </label>
            <select
              name="account_id"
              value={formData.account_id}
              onChange={handleChange}
              className="h-11 w-full rounded-xl border border-app-line bg-app-card-soft px-3 text-sm text-app-text outline-none focus:border-app-yellow"
              required
            >
              <option value="">-- اختر الحساب المحاسبي (أصل نقدية) --</option>
              {assetAccounts.map((acc) => (
                <option key={acc.id} value={acc.id}>
                  {acc.code} - {acc.name} ({acc.currency})
                </option>
              ))}
            </select>
            {errors.account_id && (
              <p className="mt-1 text-xs text-rose-500">{errors.account_id}</p>
            )}
          </div>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
          <label className="flex items-center gap-2.5 cursor-pointer rounded-xl border border-app-line/40 bg-app-card-soft/50 p-3">
            <input
              type="checkbox"
              name="is_default"
              checked={formData.is_default}
              onChange={handleChange}
              className="size-4 rounded accent-app-yellow"
            />
            <div className="text-xs">
              <span className="font-semibold text-app-text block">صندوق رئيسي افتراضي للفرع</span>
              <span className="text-app-muted">يتم اختياره تلقائياً عند قبض الاشتراكات</span>
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
              <span className="font-semibold text-app-text block">حالة الصندوق نشط</span>
              <span className="text-app-muted">جاهز لاستلام المدفوعات والصرف منه</span>
            </div>
          </label>
        </div>

        <TextAreaField
          label="ملاحظات وتوجيهات الصندوق"
          name="notes"
          value={formData.notes}
          onChange={handleChange}
          placeholder="ملاحظات عن عهدة الكاشير أو موقع الصندوق..."
          rows={3}
        />

        <div className="flex justify-end gap-3 pt-4 border-t border-app-line/30">
          <Button variant="secondary" type="button" onClick={onClose} disabled={isLoading}>
            إلغاء
          </Button>
          <Button variant="primary" type="submit" disabled={isLoading}>
            {isLoading ? "جاري الحفظ..." : safe?.id ? "حفظ التعديلات" : "إنشاء الصندوق"}
          </Button>
        </div>
      </form>
    </Modal>
  );
}
