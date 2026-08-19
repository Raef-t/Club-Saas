"use client";

import { useEffect, useState } from "react";
import Modal from "@/components/ui/Modal";
import Button from "@/components/ui/Button";
import { Field, TextAreaField } from "@/components/forms/FormControls";

export default function PartnerFormModal({
  isOpen,
  onClose,
  partner,
  branches = [],
  currentBranchId,
  remainingShare = 100,
  onSave,
  isLoading,
  errors = {},
}) {
  const [formData, setFormData] = useState({
    name: "",
    profit_share_pct: "",
    joined_at: new Date().toISOString().split("T")[0],
    branch_id: "",
    is_active: true,
    notes: "",
  });

  useEffect(() => {
    if (partner) {
      setFormData({
        name: partner.name || "",
        profit_share_pct: partner.profit_share_pct ? String(partner.profit_share_pct) : "",
        joined_at: partner.joined_at ? partner.joined_at.split("T")[0] : new Date().toISOString().split("T")[0],
        branch_id: partner.branch_id ? String(partner.branch_id) : "",
        is_active: partner.is_active ?? true,
        notes: partner.notes || "",
      });
    } else {
      setFormData({
        name: "",
        profit_share_pct: "",
        joined_at: new Date().toISOString().split("T")[0],
        branch_id: currentBranchId && currentBranchId !== "all" ? String(currentBranchId) : branches[0]?.id ? String(branches[0].id) : "",
        is_active: true,
        notes: "",
      });
    }
  }, [partner, isOpen, currentBranchId, branches]);

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
      profit_share_pct: Number(formData.profit_share_pct) || 0,
      branch_id: formData.branch_id ? Number(formData.branch_id) : null,
      name: formData.name.trim(),
    };
    await onSave(payload);
  };

  const maxAllowedPct = partner?.id
    ? remainingShare + Number(partner.profit_share_pct || 0)
    : remainingShare;

  return (
    <Modal
      open={isOpen}
      onClose={onClose}
      title={partner?.id ? `تعديل الشريك: ${partner.name}` : "تسجيل شريك جديد في رأس المال"}
      size="md"
    >
      <form onSubmit={handleSubmit} className="space-y-4" dir="rtl">
        <Field
          label="اسم الشريك بالكامل *"
          name="name"
          value={formData.name}
          onChange={handleChange}
          placeholder="مثال: السيد أحمد المنصور"
          error={errors.name}
          required
        />

        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label className="mb-1.5 block text-xs font-medium text-app-muted-light">
              نسبة الأرباح (%) * (المتاح: {maxAllowedPct.toFixed(1)}%)
            </label>
            <input
              type="number"
              step="0.01"
              min="0"
              max={maxAllowedPct}
              name="profit_share_pct"
              value={formData.profit_share_pct}
              onChange={handleChange}
              placeholder="مثال: 25.0"
              className="h-11 w-full rounded-xl border border-app-line bg-app-card-soft px-3 text-sm font-mono text-app-yellow font-bold outline-none focus:border-app-yellow"
              required
            />
            {errors.profit_share_pct && (
              <p className="mt-1 text-xs text-rose-500">{errors.profit_share_pct}</p>
            )}
          </div>

          <Field
            label="تاريخ الانضمام والشراكة *"
            type="date"
            name="joined_at"
            value={formData.joined_at}
            onChange={handleChange}
            required
          />
        </div>

        <div>
          <label className="mb-1.5 block text-xs font-medium text-app-muted-light">
            الفرع المالي للشركة *
          </label>
          <select
            name="branch_id"
            value={formData.branch_id}
            onChange={handleChange}
            className="h-11 w-full rounded-xl border border-app-line bg-app-card-soft px-3 text-sm text-app-text outline-none focus:border-app-yellow"
            required
          >
            <option value="">-- اختر الفرع --</option>
            {branches.map((b) => (
              <option key={b.id} value={b.id}>
                {b.name}
              </option>
            ))}
          </select>
          {errors.branch_id && (
            <p className="mt-1 text-xs text-rose-500">{errors.branch_id}</p>
          )}
        </div>

        <div className="pt-2">
          <label className="flex items-center gap-2.5 cursor-pointer rounded-xl border border-app-line/40 bg-app-card-soft/50 p-3">
            <input
              type="checkbox"
              name="is_active"
              checked={formData.is_active}
              onChange={handleChange}
              className="size-4 rounded accent-app-yellow"
            />
            <div className="text-xs">
              <span className="font-semibold text-app-text block">شريك نشط</span>
              <span className="text-app-muted">يتم احتساب حصته من الأرباح وتفعيل حساباته</span>
            </div>
          </label>
        </div>

        <TextAreaField
          label="ملاحظات الشراكة"
          name="notes"
          value={formData.notes}
          onChange={handleChange}
          placeholder="تفاصيل عقد الشراكة أو شروط خاصة..."
          rows={2}
        />

        <div className="flex justify-end gap-3 pt-3 border-t border-app-line/30">
          <Button variant="secondary" type="button" onClick={onClose} disabled={isLoading}>
            إلغاء
          </Button>
          <Button variant="primary" type="submit" disabled={isLoading}>
            {isLoading ? "جاري الحفظ..." : partner?.id ? "حفظ التعديلات" : "تسجيل الشريك"}
          </Button>
        </div>
      </form>
    </Modal>
  );
}
