"use client";

import { useEffect, useState } from "react";
import Image from "next/image";
import QRCode from "qrcode";
import CopyableUsername from "@/components/ui/CopyableUsername";

function QrZoomModal({ imageUrl, name, onClose }) {
  useEffect(() => {
    function handleKey(e) {
      if (e.key === "Escape") onClose();
    }
    document.addEventListener("keydown", handleKey);
    return () => document.removeEventListener("keydown", handleKey);
  }, [onClose]);

  if (!imageUrl) return null;

  return (
    <div
      className="fixed inset-0 z-[9999] flex items-center justify-center bg-black/70 backdrop-blur-sm p-4"
      onClick={onClose}
      role="dialog"
      aria-modal="true"
      aria-label={`رمز QR الخاص بـ ${name}`}
    >
      <div
        className="relative flex flex-col items-center gap-4 rounded-2xl border border-app-line bg-white p-5 shadow-2xl"
        onClick={(e) => e.stopPropagation()}
      >
        <button
          type="button"
          onClick={onClose}
          className="absolute -top-3 -left-3 grid size-8 place-items-center rounded-full border border-app-line bg-app-bg text-app-muted-light shadow-md hover:bg-app-card-soft hover:text-white transition"
          aria-label="إغلاق"
        >
          ✕
        </button>

        <button
          type="button"
          onClick={onClose}
          className="group relative overflow-hidden rounded-xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-app-yellow"
          aria-label={`تصغير رمز QR الخاص بـ ${name}`}
          title="اضغط لتصغير رمز QR"
        >
          <Image
            src={imageUrl}
            alt={`رمز QR الخاص بـ ${name}`}
            width={320}
            height={320}
            unoptimized
            className="size-[min(80vw,320px)] object-contain"
            priority
          />
          <span className="pointer-events-none absolute inset-x-0 bottom-0 bg-black/70 py-2 text-center text-xs font-semibold text-app-yellow opacity-0 transition group-hover:opacity-100 group-focus-visible:opacity-100">
            اضغط للتصغير
          </span>
        </button>

        <p className="text-center text-xs text-slate-500">{name}</p>
      </div>
    </div>
  );
}

function InlineQrCode({ value, name }) {
  const [imageUrl, setImageUrl] = useState("");
  const [failed, setFailed] = useState(false);
  const [zoomed, setZoomed] = useState(false);

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

  function handleClick(e) {
    e?.stopPropagation();
    e?.preventDefault();
    if (!imageUrl) return;
    setZoomed(true);
  }

  return (
    <>
      <button
        type="button"
        onClick={handleClick}
        disabled={!imageUrl}
        title={imageUrl ? "اضغط لتكبير رمز QR" : ""}
        aria-label={imageUrl ? `تكبير رمز QR الخاص بـ ${name}` : undefined}
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

            <div className="absolute inset-0 flex flex-col items-center justify-center bg-black/65 opacity-0 transition-opacity duration-200 group-hover:opacity-100 p-1 text-center pointer-events-none">
              <span className="text-[10px] font-bold text-app-yellow leading-tight">
                اضغط لتكبير
                <br />
                رمز QR
              </span>
            </div>
          </>
        ) : (
          <span className="px-2 text-center text-[10px] leading-4 text-slate-500">
            {failed ? "تعذر عرض الرمز" : value ? "جارٍ التحميل..." : "QR غير متوفر"}
          </span>
        )}
      </button>

      {zoomed && <QrZoomModal imageUrl={imageUrl} name={name} onClose={() => setZoomed(false)} />}
    </>
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
        {username && (
          <div className="mt-1.5 min-w-0">
            <CopyableUsername username={username} className="max-w-full" />
          </div>
        )}
        {status?.label && (
          <span
            className={`mt-3 inline-flex rounded-full px-3 py-1 text-[11px] font-semibold ${status.className || "bg-app-card-hover text-app-muted-light"}`}
          >
            {status.label}
          </span>
        )}
      </div>

      <InlineQrCode value={qrCode} name={displayName} />
    </section>
  );
}
