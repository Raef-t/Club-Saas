"use client";

import { useEffect } from "react";
import Button from "@/components/ui/Button";
import { CheckIcon, TrashIcon, XIcon } from "@/components/icons/Icons";

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
  requiredConfirmation,
  confirmationValue = "",
  onConfirmationChange,
  confirmationLabel,
  children,
}) {
  const confirmationMatches = !requiredConfirmation || confirmationValue === requiredConfirmation;

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
        className="absolute inset-0 cursor-default bg-[var(--app-overlay)] backdrop-blur-md transition-opacity"
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
          className="absolute end-4 top-4 grid size-8 place-items-center rounded-lg border border-app-line bg-app-card-soft text-app-muted-light transition hover:border-app-yellow/60 hover:text-app-yellow disabled:opacity-50"
          aria-label="إغلاق"
        >
          <XIcon className="size-4" />
        </button>

        {/* Content */}
        <div className="mt-2 text-right">
          <div
            className={`mx-auto mb-4 flex size-12 items-center justify-center rounded-full ${
              tone === "danger" ? "bg-app-red/10 text-app-red" : "bg-app-yellow/10 text-app-yellow"
            }`}
          >
            {tone === "danger" ? (
              <TrashIcon className="size-6" />
            ) : (
              <CheckIcon className="size-6" />
            )}
          </div>

          <h3 className="text-center text-lg font-medium text-app-text">{title}</h3>
          <p className="mt-3 text-center text-sm leading-relaxed text-app-muted-light">{message}</p>

          {requiredConfirmation && (
            <label className="mt-5 block text-right">
              <span className="mb-2 block text-xs font-medium text-app-muted-light">
                {confirmationLabel || (
                  <>
                    اكتب <bdi className="font-semibold text-app-text">{requiredConfirmation}</bdi>{" "}
                    للتأكيد
                  </>
                )}
              </span>
              <input
                type="text"
                dir="ltr"
                autoComplete="off"
                spellCheck={false}
                className="app-input h-11 w-full px-3 text-left text-sm text-app-text outline-none transition focus:border-app-red/70"
                value={confirmationValue}
                onChange={(event) => onConfirmationChange?.(event.target.value)}
                disabled={isLoading}
                placeholder={requiredConfirmation}
              />
            </label>
          )}

          {children && <div className="mt-4 text-right">{children}</div>}
        </div>

        {/* Actions */}
        <div className="mt-6 flex gap-3">
          <Button
            type="button"
            tone={tone === "danger" ? "danger" : "primary"}
            className="h-11 flex-1 font-medium"
            loading={isLoading}
            disabled={isLoading || !confirmationMatches}
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
