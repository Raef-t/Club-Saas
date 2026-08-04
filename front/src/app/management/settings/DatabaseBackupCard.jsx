"use client";

import { useState } from "react";
import Button from "@/components/ui/Button";
import { DatabaseIcon, DownloadIcon } from "@/components/icons/Icons";

/**
 * Modern, neatly styled card allowing administrators to generate and download
 * a server-compressed ZIP backup of the MySQL database.
 */
export default function DatabaseBackupCard() {
  const [isDownloading, setIsDownloading] = useState(false);
  const [error, setError] = useState(null);
  const [successMessage, setSuccessMessage] = useState(null);

  async function handleDownloadBackup() {
    try {
      setIsDownloading(true);
      setError(null);
      setSuccessMessage(null);

      const response = await fetch("/api/backend/system/backup/download", {
        method: "GET",
      });

      if (!response.ok) {
        let errMsg = "فشل في إنشاء أو تحميل النسخة الاحتياطية.";
        try {
          const json = await response.json();
          if (json?.message) errMsg = json.message;
        } catch {
          // Response is not JSON
        }
        throw new Error(errMsg);
      }

      const blob = await response.blob();
      const contentDisposition = response.headers.get("content-disposition");
      let filename = `backup_db_${new Date().toISOString().slice(0, 10)}.zip`;

      if (contentDisposition) {
        const match = contentDisposition.match(/filename="?([^";]+)"?/);
        if (match && match[1]) {
          filename = match[1];
        }
      }

      const downloadUrl = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = downloadUrl;
      link.download = filename;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(downloadUrl);

      setSuccessMessage("تم تجهيز وتحميل النسخة الاحتياطية بنجاح على جهازك.");
      setTimeout(() => setSuccessMessage(null), 6000);
    } catch (err) {
      setError(err.message || "حدث خطأ غير متوقع أثناء تحميل قاعدة البيانات.");
    } finally {
      setIsDownloading(false);
    }
  }

  return (
    <div className="mt-8 rounded-2xl border border-app-line bg-app-panel-soft p-6 shadow-sm">
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div className="flex items-start gap-4">
          <div className="flex size-12 shrink-0 items-center justify-center rounded-xl bg-app-yellow-soft text-app-yellow border border-app-yellow/20">
            <DatabaseIcon className="size-6" />
          </div>
          <div>
            <h4 className="text-right text-base font-semibold text-app-text">
              النسخة الاحتياطية لقاعدة البيانات
            </h4>
            <p className="mt-1 text-right text-sm text-app-muted-light max-w-xl">
              توليد نسخة احتياطية من كافة بيانات النظام، تجميعها وضغطها في ملف ZIP على السيرفر، ثم تنزيلها مباشرة إلى جهازك.
            </p>
          </div>
        </div>

        <div className="flex shrink-0 items-center justify-end">
          <Button
            type="button"
            tone="primary"
            onClick={handleDownloadBackup}
            loading={isDownloading}
            loadingLabel="جاري التجميع والضغط..."
            className="flex items-center gap-2 px-6 py-2.5 shadow-md transition-all hover:scale-[1.02]"
          >
            {!isDownloading && <DownloadIcon className="size-5" />}
            <span>تحميل النسخة الاحتياطية (ZIP)</span>
          </Button>
        </div>
      </div>

      {error && (
        <div className="mt-4 rounded-xl border border-red-500/30 bg-red-500/10 p-3 text-right text-sm text-red-400">
          ⚠️ {error}
        </div>
      )}

      {successMessage && (
        <div className="mt-4 rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-3 text-right text-sm text-emerald-400 flex items-center justify-between">
          <span>✅ {successMessage}</span>
        </div>
      )}
    </div>
  );
}
