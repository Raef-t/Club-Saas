"use client";

import { useEffect, useState } from "react";
import Image from "next/image";
import QRCode from "qrcode";
import CopyableUsername from "@/components/ui/CopyableUsername";
import { useToast } from "@/components/ui/Toast";

function InlineQrCode({ value, name, username }) {
  const [imageUrl, setImageUrl] = useState("");
  const [failed, setFailed] = useState(false);
  const [copied, setCopied] = useState(false);
  const toast = useToast();

  useEffect(() => {
    let active = true;

    setImageUrl("");
    setFailed(false);

    if (!value) {
      return () => {
        active = false;
      };
    }

    QRCode.toDataURL(value, {
      width: 300,
      margin: 1,
      errorCorrectionLevel: "M",
      color: {
        dark: "#111111",
        light: "#ffffff",
      },
    })
      .then((url) => {
        if (active) setImageUrl(url);
      })
      .catch(() => {
        if (active) setFailed(true);
      });

    return () => {
      active = false;
    };
  }, [value]);

  const handleClick = async (e) => {
    e?.stopPropagation();
    e?.preventDefault();
    if (!imageUrl) return;

    // 1. Download image file
    try {
      const fileName = `QR-${(name || value || "code").replace(/\s+/g, "_")}.png`;
      const link = document.createElement("a");
      link.href = imageUrl;
      link.download = fileName;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    } catch (err) {
      console.error("فشل تنزيل صورة الـ QR:", err);
    }

    // 2. Copy QR value / username to clipboard
    const textToCopy = value || username || name || "";
    try {
      if (navigator?.clipboard?.writeText) {
        await navigator.clipboard.writeText(textToCopy);
      } else {
        const textArea = document.createElement("textarea");
        textArea.value = textToCopy;
        textArea.style.position = "fixed";
        textArea.style.opacity = "0";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        document.execCommand("copy");
        document.body.removeChild(textArea);
      }
    } catch (err) {
      console.error("فشل نسخ رمز QR:", err);
    }

    // 3. User feedback
    setCopied(true);
    if (toast?.success) {
      toast.success("تم تنزيل صورة الـ QR ونسخ الرمز للحافظة");
    }

    setTimeout(() => {
      setCopied(false);
    }, 2200);
  };

  return (
    <button
      type="button"
      onClick={handleClick}
      disabled={!imageUrl}
      title={imageUrl ? "اضغط لتنزيل صورة الـ QR ونسخ الرمز للحافظة" : ""}
      aria-label={imageUrl ? `تنزيل ونسخ رمز QR الخاص بـ ${name}` : undefined}
      className={`group relative grid aspect-square w-full place-items-center overflow-hidden rounded-xl border border-app-line bg-white p-1.5 shadow-inner transition-all duration-300 ease-out ${
        imageUrl
          ? "cursor-pointer hover:scale-110 hover:z-30 hover:border-app-yellow/80 hover:shadow-[0_0_25px_rgba(242,220,46,0.4)] hover:ring-2 hover:ring-app-yellow/50 active:scale-105"
          : ""
      }`}
    >
      {imageUrl ? (
        <>
          <Image
            src={imageUrl}
            alt={`رمز QR الخاص بـ ${name}`}
            width={116}
            height={116}
            unoptimized
            className="size-full object-contain transition-transform duration-300 group-hover:scale-105"
          />

          {/* Hover overlay hint */}
          <div className="absolute inset-0 flex flex-col items-center justify-center bg-black/65 opacity-0 transition-opacity duration-200 group-hover:opacity-100 p-1 text-center pointer-events-none">
            <span className="text-[10px] font-bold text-app-yellow leading-tight">
              اضغط لتنزيل
              <br />
              ونسخ الـ QR
            </span>
          </div>

          {/* Copied feedback badge */}
          {copied && (
            <div className="absolute inset-0 z-20 flex items-center justify-center bg-app-green/95 p-1 text-center animate-in fade-in zoom-in-95 duration-150">
              <span className="text-[11px] font-bold text-white leading-tight">
                ✓ تم التنزيل
                <br />
                والنسخ!
              </span>
            </div>
          )}
        </>
      ) : (
        <span className="px-2 text-center text-[10px] leading-4 text-slate-500">
          {failed ? "تعذر عرض الرمز" : value ? "جارٍ التحميل..." : "QR غير متوفر"}
        </span>
      )}
    </button>
  );
}

/** Shared identity header for member, coach, and staff detail drawers. */
export default function ProfileIdentityCard({ name, username, qrCode, status }) {
  const displayName = name || "بدون اسم";

  return (
    <section
      className="grid grid-cols-[minmax(0,1fr)_88px] items-center gap-4 rounded-xl border border-app-line bg-app-card-soft/70 p-4 sm:grid-cols-[minmax(0,1fr)_116px] sm:p-5"
      dir="rtl"
    >
      <div className="min-w-0 text-right">
        <h3 className="truncate text-lg font-semibold text-app-text sm:text-xl">{displayName}</h3>
        <div className="mt-1.5 min-w-0">
          <CopyableUsername username={username} className="max-w-full" />
        </div>
        {status?.label && (
          <span
            className={`mt-3 inline-flex rounded-full px-3 py-1 text-[11px] font-semibold ${status.className || "bg-app-card-hover text-app-muted-light"}`}
          >
            {status.label}
          </span>
        )}
      </div>

      <InlineQrCode value={qrCode} name={displayName} username={username} />
    </section>
  );
}
