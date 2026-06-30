"use client";

import { createContext, useContext, useState, useCallback } from "react";

const ToastContext = createContext(null);

export function useToast() {
  const context = useContext(ToastContext);
  if (!context) {
    throw new Error("useToast must be used within a ToastProvider");
  }
  return context;
}

export function ToastProvider({ children }) {
  const [toasts, setToasts] = useState([]);

  const showToast = useCallback((message, type = "info") => {
    const id = Math.random().toString(36).substring(2, 9);
    setToasts((prev) => [...prev, { id, message, type }]);

    setTimeout(() => {
      setToasts((prev) => prev.filter((t) => t.id !== id));
    }, 4000);
  }, []);

  const removeToast = useCallback((id) => {
    setToasts((prev) => prev.filter((t) => t.id !== id));
  }, []);

  const toast = useMemo(() => {
    return {
      success: (msg) => showToast(msg, "success"),
      error: (msg) => showToast(msg, "error"),
      warning: (msg) => showToast(msg, "warning"),
      info: (msg) => showToast(msg, "info"),
    };
  }, [showToast]);

  return (
    <ToastContext.Provider value={toast}>
      {children}

      {/* Floating Container (RTL layout: top left is great for alerts) */}
      <div className="fixed top-6 left-6 z-[9999] flex flex-col gap-3 w-80 max-w-[calc(100vw-3rem)] pointer-events-none" dir="rtl">
        {toasts.map((t) => {
          let typeClasses = "";
          let icon = null;

          if (t.type === "success") {
            typeClasses = "border-app-green/30 bg-app-green/10 text-app-green";
            icon = (
              <svg className="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
              </svg>
            );
          } else if (t.type === "error") {
            typeClasses = "border-app-red/30 bg-app-red/10 text-app-red";
            icon = (
              <svg className="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
            );
          } else if (t.type === "warning") {
            typeClasses = "border-app-yellow/30 bg-app-yellow/10 text-app-yellow";
            icon = (
              <svg className="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
            );
          } else {
            typeClasses = "border-white/10 bg-white/5 text-app-text";
            icon = (
              <svg className="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            );
          }

          return (
            <div
              key={t.id}
              className={`flex items-start gap-3 p-4 rounded-xl border backdrop-blur-md shadow-lg pointer-events-auto transition-all duration-300 animate-slide-in ${typeClasses}`}
            >
              {icon}
              <div className="flex-1 text-right text-sm font-medium leading-relaxed">
                {t.message}
              </div>
              <button
                type="button"
                onClick={() => removeToast(t.id)}
                className="opacity-60 hover:opacity-100 p-0.5 rounded transition shrink-0"
              >
                <svg className="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                  <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          );
        })}
      </div>
    </ToastContext.Provider>
  );
}

import { useMemo } from "react";
