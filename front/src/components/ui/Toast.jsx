"use client";

import { createContext, useContext, useState, useCallback, useMemo } from "react";

const ToastContext = createContext(null);

export function useToast() {
  const context = useContext(ToastContext);
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

      {/* Floating Container (Top Center) */}
      <div
        className="fixed top-12 left-1/2 z-[9999] flex w-[420px] max-w-[calc(100vw-3rem)] -translate-x-1/2 flex-col gap-4 pointer-events-none"
        dir="rtl"
      >
        {toasts.map((t) => {
          let typeStyles = { border: "", glow: "", text: "" };
          let icon = null;

          if (t.type === "success") {
            typeStyles = {
              border: "border-app-green/30",
              glow: "bg-app-green/20",
              text: "text-app-green",
            };
            icon = (
              <svg
                className="size-5 shrink-0"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                strokeWidth={2.5}
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  d="M5 13l4 4L19 7"
                />
              </svg>
            );
          } else if (t.type === "error") {
            typeStyles = {
              border: "border-app-red/30",
              glow: "bg-app-red/20",
              text: "text-app-red",
            };
            icon = (
              <svg
                className="size-5 shrink-0"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                strokeWidth={2.5}
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                />
              </svg>
            );
          } else if (t.type === "warning") {
            typeStyles = {
              border: "border-app-yellow/30",
              glow: "bg-app-yellow/20",
              text: "text-app-yellow",
            };
            icon = (
              <svg
                className="size-5 shrink-0"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                strokeWidth={2.5}
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                />
              </svg>
            );
          } else {
            typeStyles = {
              border: "border-white/10",
              glow: "bg-white/5",
              text: "text-white",
            };
            icon = (
              <svg
                className="size-5 shrink-0"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                strokeWidth={2.5}
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                />
              </svg>
            );
          }

          return (
            <div
              key={t.id}
              className={`pointer-events-auto relative flex items-center overflow-hidden rounded-[20px] border bg-app-card p-4 shadow-[0_10px_40px_-10px_rgba(0,0,0,0.5)] transition-all duration-300 animate-slide-in ${typeStyles.border}`}
            >
              {/* Center Glow Effect */}
              <div
                className={`absolute left-1/2 top-1/2 h-20 w-40 -translate-x-1/2 -translate-y-1/2 rounded-full blur-[45px] ${typeStyles.glow}`}
              />

              {/* Content Container */}
              <div className="relative z-10 flex w-full items-center justify-between gap-4 px-2">
                {/* Icon (Right) */}
                <div className={`shrink-0 ${typeStyles.text}`}>
                  {icon}
                </div>

                {/* Text (Center) */}
                <p className={`flex-1 text-center text-sm font-medium leading-relaxed ${typeStyles.text}`}>
                  {t.message}
                </p>

                {/* Close Button (Left) */}
                <button
                  type="button"
                  onClick={() => removeToast(t.id)}
                  className={`shrink-0 rounded-lg p-1 opacity-60 transition-opacity hover:opacity-100 ${typeStyles.text}`}
                >
                  <svg
                    className="size-5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    strokeWidth={2}
                  >
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      d="M6 18L18 6M6 6l12 12"
                    />
                  </svg>
                </button>
              </div>
            </div>
          );
        })}
      </div>
    </ToastContext.Provider>
  );
}
