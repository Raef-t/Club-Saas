"use client";

import { useEffect, useState } from "react";
import Button from "@/components/ui/Button";
import Modal from "@/components/ui/Modal";
import { validateRoleName } from "./roleUtils";

export default function CreateRoleModal({ open, onClose, onSubmit, isLoading }) {
  const [name, setName] = useState("");
  const [error, setError] = useState("");

  useEffect(() => {
    if (open) {
      setName("");
      setError("");
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

    setError("");
    await onSubmit(normalizedName);
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
