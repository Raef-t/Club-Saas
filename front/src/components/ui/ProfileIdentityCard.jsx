"use client";

import { useEffect, useState } from "react";
import Image from "next/image";
import QRCode from "qrcode";
import CopyableUsername from "@/components/ui/CopyableUsername";

function InlineQrCode({ value, name }) {
  const [imageUrl, setImageUrl] = useState("");
  const [failed, setFailed] = useState(false);

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
      width: 240,
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

  return (
    <div className="grid aspect-square w-full place-items-center overflow-hidden rounded-xl border border-app-line bg-white p-1.5 shadow-inner">
      {imageUrl ? (
        <Image
          src={imageUrl}
          alt={`رمز QR الخاص بـ ${name}`}
          width={116}
          height={116}
          unoptimized
          className="size-full object-contain"
        />
      ) : (
        <span className="px-2 text-center text-[10px] leading-4 text-slate-500">
          {failed ? "تعذر عرض الرمز" : value ? "جارٍ التحميل..." : "QR غير متوفر"}
        </span>
      )}
    </div>
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

      <InlineQrCode value={qrCode} name={displayName} />
    </section>
  );
}
