"use client";

import { useState, useCallback } from "react";
import { CopyIcon, CheckIcon } from "@/components/icons/Icons";
import { useToast } from "@/components/ui/Toast";

export default function CopyableUsername({
  username,
  className = "",
  align = "start",
  showCopyIcon = true,
}) {
  const [copied, setCopied] = useState(false);
  const toast = useToast();

  const handleCopy = useCallback(
    async (e) => {
      e.stopPropagation();
      e.preventDefault();
      if (!username) return;

      try {
        if (navigator?.clipboard?.writeText) {
          await navigator.clipboard.writeText(username);
        } else {
          const textArea = document.createElement("textarea");
          textArea.value = username;
          textArea.style.position = "fixed";
          textArea.style.opacity = "0";
          document.body.appendChild(textArea);
          textArea.focus();
          textArea.select();
          document.execCommand("copy");
          document.body.removeChild(textArea);
        }

        setCopied(true);
        if (toast?.success) {
          toast.success(`تم نسخ اسم المستخدم: ${username}`);
        }

        setTimeout(() => {
          setCopied(false);
        }, 2000);
      } catch (err) {
        console.error("فشل نسخ اسم المستخدم:", err);
      }
    },
    [username, toast],
  );

  if (!username) {
    return <span className="text-app-muted-light">غير محدد</span>;
  }

  const alignClass =
    align === "center"
      ? "justify-center"
      : align === "end"
        ? "justify-end"
        : "justify-start";

  return (
    <div
      className={`group/copyable relative inline-flex items-center gap-1.5 ${alignClass} ${className}`}
    >
      <button
        type="button"
        onClick={handleCopy}
        title={copied ? "تم النسخ!" : `نسخ اسم المستخدم (${username}) - اضغط للنسخ`}
        aria-label={`نسخ اسم المستخدم ${username}`}
        className="relative inline-flex items-center gap-1.5 rounded-md px-2 py-0.5 text-start font-mono text-[11px] font-medium transition-all duration-200 ease-out origin-center cursor-pointer hover:scale-125 hover:z-50 hover:bg-app-card-hover hover:text-app-yellow hover:shadow-xl hover:ring-1 hover:ring-app-yellow/40 active:scale-110 focus:outline-none focus:ring-1 focus:ring-app-yellow/60"
        dir="ltr"
      >
        <span className="truncate tracking-wide text-app-muted-light transition-colors group-hover/copyable:text-app-yellow">
          {username}
        </span>

        {showCopyIcon && (
          <span
            className={`shrink-0 transition-all duration-200 ${
              copied
                ? "text-app-green scale-110"
                : "text-app-muted-light/60 group-hover/copyable:text-app-yellow group-hover/copyable:scale-110"
            }`}
          >
            {copied ? <CheckIcon className="size-3.5" /> : <CopyIcon className="size-3.5" />}
          </span>
        )}

        {/* Hover zoom feedback badge for copy status */}
        {copied && (
          <span
            dir="rtl"
            className="absolute -top-7 start-1/2 -translate-x-1/2 whitespace-nowrap rounded-md bg-app-green px-2 py-0.5 text-[10px] font-bold text-white shadow-lg animate-in fade-in zoom-in-95 duration-150"
          >
            تم النسخ!
          </span>
        )}
      </button>
    </div>
  );
}
