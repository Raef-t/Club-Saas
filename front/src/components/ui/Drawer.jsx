"use client";

import { useEffect } from "react";
import { XIcon } from "@/components/icons/Icons";

const sideClass = {
  right: "inset-y-0 right-0 h-full w-full max-w-[460px] translate-x-0",
  left: "inset-y-0 left-0 h-full w-full max-w-[460px] translate-x-0",
  end: "inset-y-0 right-0 h-full w-full max-w-[460px] translate-x-0",
  start: "inset-y-0 left-0 h-full w-full max-w-[460px] translate-x-0",
  bottom: "inset-x-0 bottom-0 max-h-[85vh] w-full rounded-t-2xl",
};

export default function Drawer({
  open,
  onClose,
  title,
  subtitle,
  children,
  footer,
  side = "right",
  className = "",
  contentClassName = "",
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
    <div className="fixed inset-0 z-[80]" role="dialog" aria-modal="true" dir="rtl">
      <button
        type="button"
        className="absolute inset-0 cursor-default bg-black/60 backdrop-blur-sm"
        aria-label="إغلاق"
        onClick={onClose}
      />

      <aside
        className={`app-panel absolute flex flex-col overflow-hidden border-app-line bg-app-panel text-right ${sideClass[side] || sideClass.right} ${className}`}
        dir="rtl"
      >
        <header className="flex items-start justify-between gap-4 border-b border-app-line px-5 py-4">
          <div className="min-w-0 text-right">
            {title && <h2 className="text-lg font-medium text-app-text">{title}</h2>}
            {subtitle && (
              <p className="mt-1 text-xs text-app-muted-light">{subtitle}</p>
            )}
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

        <div className={`flex-1 overflow-y-auto px-5 py-5 text-right scrollbar-thin ${contentClassName}`}>
          {children}
        </div>

        {footer && (
          <footer className="border-t border-app-line px-5 py-4">{footer}</footer>
        )}
      </aside>
    </div>
  );
}
