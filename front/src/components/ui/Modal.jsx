"use client";

import { useEffect } from "react";
import { XIcon } from "@/components/icons/Icons";

export default function Modal({
  open,
  onClose,
  title,
  subtitle,
  children,
  className = "",
  contentClassName = "",
}) {
  useEffect(() => {
    if (!open) return;

    const previousOverflow = document.body.style.overflow;
    document.body.style.overflow = "hidden";

    function handleKeyDown(event) {
      if (event.key === "Escape") onClose?.();
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
      aria-labelledby="app-modal-title"
      dir="rtl"
    >
      <button
        type="button"
        className="absolute inset-0 cursor-default bg-[var(--app-overlay)] backdrop-blur-md"
        aria-label="إغلاق"
        onClick={onClose}
      />

      <section
        className={`card-shell relative z-10 flex max-h-[90vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl border border-app-line bg-app-panel shadow-2xl ${className}`}
      >
        <header className="flex items-start justify-between gap-4 border-b border-app-line px-5 py-4">
          <div className="min-w-0 text-right">
            {title && (
              <h2 id="app-modal-title" className="text-lg font-medium text-app-text">
                {title}
              </h2>
            )}
            {subtitle && <p className="mt-1 truncate text-xs text-app-muted-light">{subtitle}</p>}
          </div>

          <button
            type="button"
            className="grid size-9 shrink-0 place-items-center rounded-lg border border-app-line bg-app-card-soft text-app-muted-light transition hover:border-app-yellow/60 hover:text-app-yellow"
            aria-label="إغلاق"
            onClick={onClose}
          >
            <XIcon className="size-4" />
          </button>
        </header>

        <div className={`flex-1 overflow-y-auto px-5 py-5 text-right ${contentClassName}`}>
          {children}
        </div>
      </section>
    </div>
  );
}
