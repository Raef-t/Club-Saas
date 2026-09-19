"use client";

import { useEffect, useState } from "react";
import Button from "@/components/ui/Button";
import Modal from "@/components/ui/Modal";
import ToggleSwitch from "@/components/ui/ToggleSwitch";
import { validateRoleName } from "./roleUtils";

export default function CreateRoleModal({ open, onClose, onSubmit, isLoading }) {
  const [name, setName] = useState("");
  const [nameAr, setNameAr] = useState("");
  const [isVisible, setIsVisible] = useState(true);
  const [error, setError] = useState("");
  const [nameArError, setNameArError] = useState("");

  useEffect(() => {
    if (open) {
      setName("");
      setNameAr("");
      setIsVisible(true);
      setError("");
      setNameArError("");
    }
  }, [open]);

  async function handleSubmit(event) {
    event.preventDefault();
    const normalizedName = name.trim();
    const validationError = validateRoleName(normalizedName);
    if (validationError) {
      setError(validationError);
      return;
    }

    const normalizedNameAr = nameAr.trim();
    if (normalizedNameAr.length < 2) {
      setNameArError("الاسم العربي مطلوب ويجب أن يكون حرفين على الأقل.");
      return;
    }

    setError("");
    setNameArError("");
    await onSubmit({
      name: normalizedName,
      name_ar: normalizedNameAr,
      is_visible: isVisible,
    });
  }

  return (
    <Modal
      open={open}
      onClose={isLoading ? undefined : onClose}
      title="إنشاء دور جديد"
      subtitle="أنشئ الدور أولاً، ثم افتحه لتحديد صلاحياته."
      className="max-w-lg"
    >
      <form onSubmit={handleSubmit} className="space-y-5">
        <label className="block text-right">
          <span className="mb-2 block text-sm font-medium text-app-text">اسم الدور</span>
          <input
            autoFocus
            type="text"
            dir="ltr"
            autoComplete="off"
            spellCheck={false}
            value={name}
            onChange={(event) => {
              setName(event.target.value.toLowerCase().replace(/\s+/g, "_"));
              if (error) setError("");
            }}
            className="app-input h-11 w-full px-3 text-left text-sm text-app-text outline-none transition focus:border-app-yellow/70"
            placeholder="member_manager"
            aria-invalid={Boolean(error)}
            aria-describedby="role-name-help role-name-error"
            disabled={isLoading}
          />
          <p id="role-name-help" className="mt-2 text-xs leading-5 text-app-muted-light">
            استخدم أحرفاً إنجليزية صغيرة وأرقاماً وشرطة سفلية فقط، مثل: member_manager.
          </p>
          {error && (
            <p id="role-name-error" className="mt-2 text-xs text-app-red">
              {error}
            </p>
          )}
        </label>

        <label className="block text-right">
          <span className="mb-2 block text-sm font-medium text-app-text">
            الاسم العربي للدور
            <span className="text-app-red"> *</span>
          </span>
          <input
            type="text"
            dir="rtl"
            autoComplete="off"
            value={nameAr}
            onChange={(event) => {
              setNameAr(event.target.value);
              if (nameArError) setNameArError("");
            }}
            className="app-input h-11 w-full px-3 text-right text-sm text-app-text outline-none transition focus:border-app-yellow/70"
            placeholder="مثال: مشرف الاستقبال"
            aria-invalid={Boolean(nameArError)}
            aria-describedby="role-name-ar-error"
            disabled={isLoading}
          />
          {nameArError && (
            <p id="role-name-ar-error" className="mt-2 text-xs text-app-red">
              {nameArError}
            </p>
          )}
        </label>

        <div className="flex items-center justify-between rounded-lg border border-app-line bg-app-card-soft/55 px-4 py-3">
          <div className="text-right">
            <p className="text-sm font-medium text-app-text">إظهار الدور</p>
            <p className="mt-0.5 text-xs text-app-muted-light">
              يظهر الدور ضمن خيارات تعيين المستخدمين.
            </p>
          </div>
          <ToggleSwitch
            checked={isVisible}
            onChange={(event) => setIsVisible(event.target.checked)}
            disabled={isLoading}
          />
        </div>

        <div className="flex gap-3 border-t border-app-line pt-4">
          <Button type="submit" className="flex-1" loading={isLoading}>
            إنشاء الدور
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
