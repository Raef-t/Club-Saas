"use client";

import { useEffect, useState } from "react";
import Button from "@/components/ui/Button";
import Modal from "@/components/ui/Modal";
import ToggleSwitch from "@/components/ui/ToggleSwitch";

export default function EditRoleModal({ open, role, onClose, onSubmit, isLoading }) {
  const [nameAr, setNameAr] = useState("");
  const [isVisible, setIsVisible] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    if (open && role) {
      setNameAr(role.name_ar || "");
      setIsVisible(role.is_visible ?? true);
      setError("");
    }
  }, [open, role]);

  async function handleSubmit(event) {
    event.preventDefault();
    const trimmed = nameAr.trim();
    if (!trimmed) {
      setError("الاسم العربي مطلوب.");
      return;
    }
    if (trimmed.length < 2) {
      setError("الاسم يجب أن يكون حرفين على الأقل.");
      return;
    }
    setError("");
    await onSubmit({ id: role.id, name_ar: trimmed, is_visible: isVisible });
  }

  if (!role) return null;

  return (
    <Modal
      open={open}
      onClose={isLoading ? undefined : onClose}
      title="تعديل الدور"
      subtitle={`تعديل بيانات الدور: ${role.name}`}
      className="max-w-lg"
    >
      <form onSubmit={handleSubmit} className="space-y-5">
        {/* اسم الدور التقني — للعرض فقط */}
        <div className="rounded-lg border border-app-line bg-app-card-soft/55 px-4 py-3">
          <p className="text-[11px] text-app-muted-light">الاسم التقني (غير قابل للتعديل)</p>
          <bdi className="mt-1 block font-mono text-sm text-app-yellow" dir="ltr">
            {role.name}
          </bdi>
        </div>

        {/* الاسم العربي */}
        <label className="block text-right">
          <span className="mb-2 block text-sm font-medium text-app-text">
            الاسم العربي للدور
            <span className="text-app-red"> *</span>
          </span>
          <input
            autoFocus
            type="text"
            dir="rtl"
            autoComplete="off"
            value={nameAr}
            onChange={(event) => {
              setNameAr(event.target.value);
              if (error) setError("");
            }}
            className="app-input h-11 w-full px-3 text-right text-sm text-app-text outline-none transition focus:border-app-yellow/70"
            placeholder="مثال: مدير شؤون الأعضاء"
            aria-invalid={Boolean(error)}
            aria-describedby="edit-role-name-ar-error"
            disabled={isLoading}
          />
          {error && (
            <p id="edit-role-name-ar-error" className="mt-2 text-xs text-app-red">
              {error}
            </p>
          )}
        </label>

        {/* الظهور */}
        <div className="flex items-center justify-between rounded-lg border border-app-line bg-app-card-soft/55 px-4 py-3">
          <div className="text-right">
            <p className="text-sm font-medium text-app-text">إظهار الدور</p>
            <p className="mt-0.5 text-xs text-app-muted-light">
              هل يظهر هذا الدور عند تعيين المستخدمين؟
            </p>
          </div>
          <ToggleSwitch
            checked={isVisible}
            onChange={(e) => setIsVisible(e.target.checked)}
            disabled={isLoading}
          />
        </div>

        <div className="flex gap-3 border-t border-app-line pt-4">
          <Button type="submit" className="flex-1" loading={isLoading}>
            حفظ التعديلات
          </Button>
          <Button
            type="button"
            tone="outline"
            className="flex-1"
            onClick={onClose}
            disabled={isLoading}
          >
            إلغاء
          </Button>
        </div>
      </form>
    </Modal>
  );
}
