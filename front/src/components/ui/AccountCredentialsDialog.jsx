"use client";

import { useEffect, useState } from "react";
import Button from "@/components/ui/Button";
import Modal from "@/components/ui/Modal";

function CredentialRow({ label, value, onCopy, copied }) {
  const hasValue = Boolean(value);

  return (
    <div className="rounded-xl border border-app-line bg-app-card-soft p-4">
      <p className="text-xs text-app-muted-light">{label}</p>
      <div className="mt-2 flex items-center justify-between gap-3" dir="ltr">
        <code
          className={`min-w-0 flex-1 truncate text-sm font-semibold ${
            hasValue ? "text-app-yellow" : "text-app-muted-light"
          }`}
        >
          {value || "غير متوفر"}
        </code>
        {hasValue && (
          <Button
            type="button"
            tone="outline"
            className="h-8 px-3 text-xs"
            onClick={() => onCopy(value)}
          >
            {copied ? "تم النسخ" : "نسخ"}
          </Button>
        )}
      </div>
    </div>
  );
}

export default function AccountCredentialsDialog({
  credentials,
  entityLabel,
  onClose,
  closeLabel,
}) {
  const [copiedValue, setCopiedValue] = useState("");

  useEffect(() => {
    setCopiedValue("");
  }, [credentials]);

  async function copyValue(value) {
    try {
      await navigator.clipboard.writeText(value);
      setCopiedValue(value);
      window.setTimeout(() => setCopiedValue(""), 1800);
    } catch {
      setCopiedValue("");
    }
  }

  return (
    <Modal
      open={Boolean(credentials)}
      onClose={onClose}
      title={`بيانات حساب ${entityLabel}`}
      subtitle={`تمت إضافة ${entityLabel} بنجاح`}
      className="max-w-lg"
    >
      <div className="space-y-4">
        <p className="text-center text-sm leading-6 text-app-green">
          احتفظ ببيانات الدخول وسلّمها للمستخدم بطريقة آمنة، فكلمة المرور قد لا تظهر مرة أخرى.
        </p>

        <CredentialRow
          label="اسم المستخدم"
          value={credentials?.username}
          onCopy={copyValue}
          copied={copiedValue === credentials?.username}
        />
        <CredentialRow
          label="كلمة المرور"
          value={credentials?.password}
          onCopy={copyValue}
          copied={copiedValue === credentials?.password}
        />

        <Button type="button" className="h-11 w-full text-black" onClick={onClose}>
          {closeLabel}
        </Button>
      </div>
    </Modal>
  );
}
