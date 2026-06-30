"use client";

import { useEffect } from "react";
import Button from "@/components/ui/Button";
import { TrashIcon, XIcon } from "@/components/icons/Icons";

export default function ConfirmDialog({
  open,
  onClose,
  onConfirm,
  title = "تأكيد الحذف",
  message = "هل أنت متأكد من رغبتك في حذف هذا العنصر؟ لا يمكن التراجع عن هذا الإجراء.",
  confirmLabel = "حذف",
  cancelLabel = "إلغاء",
  isLoading = false,
  tone = "danger",
}) {
  useEffect(() => {
    if (!open) return;

    const previousOverflow = document.body.style.overflow;
    document.body.style.overflow = "hidden";

    function handleKeyDown(event) {
      if (event.key === "Escape") {
        onClose?.();
      }
    }

    document.addEventListener("keydown", handleKeyDown);

    return () => {
      document.body.style.overflow = previousOverflow;
      document.removeEventListener("keydown", handleKeyDown);
    };
  }, [onClose, open]);

  if (!open) return null;

  return (
    <div
      className="fixed inset-0 z-[90] flex items-center justify-center p-4"
      role="dialog"
      aria-modal="true"
      dir="rtl"
    >
      {/* Backdrop */}
      <button
        type="button"
        className="absolute inset-0 cursor-default bg-black/70 backdrop-blur-md transition-opacity"
        aria-label="إغلاق"
        onClick={isLoading ? undefined : onClose}
        disabled={isLoading}
      />

      {/* Dialog Box */}
      <div className="card-shell relative z-10 w-full max-w-md overflow-hidden rounded-2xl border border-app-line bg-app-panel p-6 shadow-2xl transition-all">
        {/* Close Button */}
        <button
          type="button"
          onClick={onClose}
          disabled={isLoading}
          className="absolute left-4 top-4 grid size-8 place-items-center rounded-lg border border-app-line bg-app-card-soft text-app-muted-light transition hover:border-app-yellow/60 hover:text-app-yellow disabled:opacity-50"
          aria-label="إغلاق"
        >
          <XIcon className="size-4" />
        </button>

        {/* Content */}
        <div className="mt-2 text-right">
          <div className="mx-auto mb-4 flex size-12 items-center justify-center rounded-full bg-app-red/10 text-app-red">
            <TrashIcon className="size-6" />
          </div>

          <h3 className="text-center text-lg font-medium text-white">
            {title}
          </h3>
          <p className="mt-3 text-center text-sm leading-relaxed text-app-muted-light">
            {message}
          </p>
        </div>

        {/* Actions */}
        <div className="mt-6 flex flex-row-reverse gap-3">
          <Button
            type="button"
            tone={tone === "danger" ? "danger" : "primary"}
            className="h-11 flex-1 font-medium"
            loading={isLoading}
            onClick={onConfirm}
          >
            {confirmLabel}
          </Button>
          <Button
            type="button"
            tone="outline"
            className="h-11 flex-1 font-medium"
            disabled={isLoading}
            onClick={onClose}
          >
            {cancelLabel}
          </Button>
        </div>
      </div>
    </div>
  );
}
