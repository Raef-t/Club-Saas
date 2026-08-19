"use client";

import { useState } from "react";
import Modal from "@/components/ui/Modal";
import Button from "@/components/ui/Button";
import { TextAreaField } from "@/components/forms/FormControls";

export default function JournalActionModal({
  isOpen,
  onClose,
  type,
  journal,
  onExecute,
  isLoading,
}) {
  const [reason, setReason] = useState("");

  if (!journal) return null;

  const titles = {
    post: `ترحيل السند المحاسبي: ${journal.number || `#${journal.id}`}`,
    reverse: `عكس السند المحاسبي: ${journal.number || `#${journal.id}`}`,
    cancel: `إلغاء السند المحاسبي: ${journal.number || `#${journal.id}`}`,
  };

  const descriptions = {
    post: "هل أنت متأكد من رغبتك في ترحيل هذا السند؟ سيتم التأثير مباشرة على أرصدة الحسابات والصناديق ولن يمكن تعديل بيانات السند بعد الترحيل.",
    reverse: "سيتم توليد قيد عكسي تلقائياً لموازنة وتصفير أثر هذا السند. يرجى توضيح سبب عكس القيد.",
    cancel: "هل أنت متأكد من رغبتك في إلغاء هذا السند كلياً؟ يرجى إدخال سبب الإلغاء.",
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    await onExecute(reason);
    setReason("");
  };

  return (
    <Modal
      open={isOpen}
      onClose={onClose}
      title={titles[type] || "إجراء على السند"}
      size="md"
    >
      <form onSubmit={handleSubmit} className="space-y-4" dir="rtl">
        <p className="text-sm text-app-muted-light">{descriptions[type]}</p>

        {(type === "reverse" || type === "cancel") && (
          <TextAreaField
            label="السبب / المبرر *"
            value={reason}
            onChange={(e) => setReason(e.target.value)}
            placeholder="يرجى كتابة سبب الإلغاء أو العكس بالتفصيل..."
            rows={3}
            required
          />
        )}

        <div className="flex justify-end gap-3 pt-3 border-t border-app-line/30">
          <Button variant="secondary" type="button" onClick={onClose} disabled={isLoading}>
            تراجع
          </Button>
          <Button
            variant={type === "cancel" ? "danger" : type === "reverse" ? "warning" : "primary"}
            type="submit"
            disabled={isLoading || ((type === "reverse" || type === "cancel") && !reason.trim())}
          >
            {isLoading ? "جاري المعالجة..." : "تأكيد الإجراء"}
          </Button>
        </div>
      </form>
    </Modal>
  );
}
