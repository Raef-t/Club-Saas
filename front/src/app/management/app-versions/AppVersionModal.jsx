"use client";

import { useEffect, useState, useRef } from "react";
import Modal from "@/components/ui/Modal";
import Button from "@/components/ui/Button";
import ToggleSwitch from "@/components/ui/ToggleSwitch";
import { DownloadIcon, FileUpIcon, XIcon } from "@/components/icons/Icons";

export default function AppVersionModal({
  open,
  onClose,
  initialData = null,
  onSubmit,
  isSubmitting = false,
}) {
  const isEditing = Boolean(initialData);

  const [platform, setPlatform] = useState("android");
  const [versionNumber, setVersionNumber] = useState("");
  const [buildNumber, setBuildNumber] = useState("");
  const [sourceType, setSourceType] = useState("file"); // 'file' | 'url'
  const [downloadUrl, setDownloadUrl] = useState("");
  const [file, setFile] = useState(null);
  const [isForceUpdate, setIsForceUpdate] = useState(false);
  const [isActive, setIsActive] = useState(true);
  const [releaseNotes, setReleaseNotes] = useState("");
  const [validationError, setValidationError] = useState("");

  const fileInputRef = useRef(null);

  // Initialize or reset form state
  useEffect(() => {
    if (initialData) {
      setPlatform(initialData.platform || "android");
      setVersionNumber(initialData.version_number || "");
      setBuildNumber(String(initialData.build_number ?? ""));
      setDownloadUrl(initialData.download_url || "");
      setSourceType(initialData.file_path ? "file" : "url");
      setFile(null);
      setIsForceUpdate(Boolean(initialData.is_force_update));
      setIsActive(initialData.is_active !== undefined ? Boolean(initialData.is_active) : true);
      setReleaseNotes(initialData.release_notes || "");
      setValidationError("");
    } else {
      setPlatform("android");
      setVersionNumber("");
      setBuildNumber("1");
      setSourceType("file");
      setDownloadUrl("");
      setFile(null);
      setIsForceUpdate(false);
      setIsActive(true);
      setReleaseNotes("");
      setValidationError("");
    }
  }, [initialData, open]);

  function handleFileChange(e) {
    const selected = e.target.files?.[0];
    if (!selected) return;

    if (selected.size > 150 * 1024 * 1024) {
      setValidationError("حجم الملف يتجاوز الحد المسموح به (150 ميغابايت).");
      return;
    }

    setFile(selected);
    setValidationError("");
  }

  async function handleSubmit(e) {
    e.preventDefault();
    setValidationError("");

    if (!versionNumber.trim()) {
      setValidationError("يرجى إدخال رقم الإصدار (مثل 1.0.0).");
      return;
    }

    const buildNum = parseInt(buildNumber, 10);
    if (isNaN(buildNum) || buildNum < 1) {
      setValidationError("يرجى إدخال رقم بناء صالح (أكبر من 0).");
      return;
    }

    if (!isEditing && sourceType === "file" && !file) {
      setValidationError("يرجى اختيار ملف التطبيق للرفع.");
      return;
    }

    if (sourceType === "url" && !downloadUrl.trim()) {
      setValidationError("يرجى إدخال رابط التحميل المباشر.");
      return;
    }

    const formData = new FormData();
    formData.append("platform", platform);
    formData.append("version_number", versionNumber.trim());
    formData.append("build_number", buildNum);
    formData.append("is_force_update", isForceUpdate ? "1" : "0");
    formData.append("is_active", isActive ? "1" : "0");
    if (releaseNotes.trim()) {
      formData.append("release_notes", releaseNotes.trim());
    }

    if (sourceType === "file" && file) {
      formData.append("app_file", file);
    } else if (sourceType === "url" && downloadUrl.trim()) {
      formData.append("download_url", downloadUrl.trim());
    }

    try {
      await onSubmit(formData);
    } catch {
      // Error handled by parent hook toast
    }
  }

  return (
    <Modal
      open={open}
      onClose={onClose}
      title={isEditing ? "تعديل إصدار تطبيق المتدرب" : "إضافة إصدار جديد لتطبيق المتدرب"}
      subtitle="إدارة ونشر إصدارات تطبيق المتدرب لأنظمة Android و iOS"
      className="max-w-xl"
    >
      <form onSubmit={handleSubmit} className="flex flex-col gap-5 p-6" dir="rtl">
        {validationError && (
          <div className="rounded-xl border border-app-red/30 bg-app-red/10 p-3 text-xs text-app-red">
            {validationError}
          </div>
        )}

        {/* Platform Selection */}
        <div>
          <label className="block text-xs font-medium text-app-text mb-2">نظام التشغيل</label>
          <div className="grid grid-cols-2 gap-3">
            <button
              type="button"
              onClick={() => setPlatform("android")}
              className={`flex items-center justify-center gap-2.5 rounded-xl border p-3 transition text-sm font-medium ${
                platform === "android"
                  ? "border-app-yellow bg-app-yellow-soft text-app-text font-bold"
                  : "border-app-line bg-app-card hover:bg-app-card-hover text-app-muted-light"
              }`}
            >
              <svg className="size-5 fill-current text-green-500" viewBox="0 0 24 24">
                <path d="M17.523 15.3414c-.5511 0-.9993-.4486-.9993-.9997s.4482-.9993.9993-.9993c.551 0 .9993.4482.9993.9993.0001.5511-.4483.9997-.9993.9997m-11.046 0c-.5511 0-.9993-.4486-.9993-.9997s.4482-.9993.9993-.9993c.551 0 .9993.4482.9993.9993 0 .5511-.4483.9997-.9993.9997m11.4045-6.02l1.9973-3.4592a.416.416 0 00-.1521-.5676.416.416 0 00-.5676.1521l-2.0223 3.503C15.5837 7.915 13.8427 7.4 12 7.4s-3.5837.515-5.1368 1.5498L4.8409 5.4468a.4161.4161 0 00-.5677-.1521.4157.4157 0 00-.152 5676l1.9973 3.4592C2.6889 11.1867.3432 14.6589 0 18.741h24c-.3432-4.0821-2.6889-7.5543-6.1185-9.4196" />
              </svg>
              <span>Android (APK)</span>
            </button>

            <button
              type="button"
              onClick={() => setPlatform("ios")}
              className={`flex items-center justify-center gap-2.5 rounded-xl border p-3 transition text-sm font-medium ${
                platform === "ios"
                  ? "border-app-yellow bg-app-yellow-soft text-app-text font-bold"
                  : "border-app-line bg-app-card hover:bg-app-card-hover text-app-muted-light"
              }`}
            >
              <svg className="size-5 fill-current" viewBox="0 0 24 24">
                <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M15.97 6.37c.61-.75 1.04-1.8 0.92-2.85-.9.04-2 .6-2.64 1.35-.56.65-1.05 1.71-.92 2.74 1.01.08 2.03-.49 2.64-1.24z" />
              </svg>
              <span>iOS (Apple)</span>
            </button>
          </div>
        </div>

        {/* Version & Build Numbers */}
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label className="block text-xs font-medium text-app-text mb-1.5">
              رقم الإصدار <span className="text-app-red">*</span>
            </label>
            <input
              type="text"
              placeholder="مثال: 1.0.0"
              value={versionNumber}
              onChange={(e) => setVersionNumber(e.target.value)}
              className="w-full rounded-xl border border-app-line bg-app-card px-3.5 py-2.5 text-sm text-app-text outline-none focus:border-app-yellow transition"
              dir="ltr"
              required
            />
          </div>

          <div>
            <label className="block text-xs font-medium text-app-text mb-1.5">
              رقم البناء (Build Number) <span className="text-app-red">*</span>
            </label>
            <input
              type="number"
              min="1"
              placeholder="مثال: 1"
              value={buildNumber}
              onChange={(e) => setBuildNumber(e.target.value)}
              className="w-full rounded-xl border border-app-line bg-app-card px-3.5 py-2.5 text-sm text-app-text outline-none focus:border-app-yellow transition"
              dir="ltr"
              required
            />
          </div>
        </div>

        {/* Upload Mode Selector */}
        <div>
          <label className="block text-xs font-medium text-app-text mb-2">طريقة تزويد ملف الإصدار</label>
          <div className="flex rounded-xl border border-app-line bg-app-card p-1">
            <button
              type="button"
              onClick={() => setSourceType("file")}
              className={`flex-1 rounded-lg py-1.5 text-xs font-medium transition ${
                sourceType === "file"
                  ? "bg-app-yellow text-app-on-accent font-bold shadow-sm"
                  : "text-app-muted-light hover:text-app-text"
              }`}
            >
              رفع ملف التطبيق إلى السيرفر (.apk / .ipa)
            </button>
            <button
              type="button"
              onClick={() => setSourceType("url")}
              className={`flex-1 rounded-lg py-1.5 text-xs font-medium transition ${
                sourceType === "url"
                  ? "bg-app-yellow text-app-on-accent font-bold shadow-sm"
                  : "text-app-muted-light hover:text-app-text"
              }`}
            >
              إدخال رابط تحميل مباشر
            </button>
          </div>
        </div>

        {/* File Upload Zone */}
        {sourceType === "file" ? (
          <div>
            <input
              ref={fileInputRef}
              type="file"
              accept=".apk,.ipa,.aab,application/vnd.android.package-archive"
              onChange={handleFileChange}
              className="hidden"
            />
            <div
              onClick={() => fileInputRef.current?.click()}
              className="flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-app-line hover:border-app-yellow/70 bg-app-card-soft p-6 cursor-pointer transition text-center group"
            >
              <div className="grid size-12 place-items-center rounded-full bg-app-yellow-soft text-app-yellow group-hover:scale-105 transition">
                <FileUpIcon className="size-6" />
              </div>
              <p className="mt-2 text-sm font-medium text-app-text">
                {file ? file.name : "اضغط هنا لاختيار ملف التطبيق"}
              </p>
              <p className="mt-1 text-xs text-app-muted-light">
                {file
                  ? `الحجم: ${(file.size / (1024 * 1024)).toFixed(2)} ميغابايت`
                  : isEditing && initialData?.download_url
                  ? "يوجد ملف مرفوع حالياً (اتركه فارغاً للإبقاء على الملف الحالي)"
                  : "يدعم صيغ .apk و .ipa حتى حجم 150 ميغابايت"}
              </p>
            </div>
          </div>
        ) : (
          <div>
            <label className="block text-xs font-medium text-app-text mb-1.5">
              رابط التحميل المباشر <span className="text-app-red">*</span>
            </label>
            <input
              type="url"
              placeholder="https://example.com/downloads/trainee-app.apk"
              value={downloadUrl}
              onChange={(e) => setDownloadUrl(e.target.value)}
              className="w-full rounded-xl border border-app-line bg-app-card px-3.5 py-2.5 text-sm text-app-text outline-none focus:border-app-yellow transition"
              dir="ltr"
              required={sourceType === "url"}
            />
          </div>
        )}

        {/* Force Update & Active Toggles */}
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
          <div className="flex items-center justify-between rounded-xl border border-app-line bg-app-card p-3">
            <div>
              <span className="block text-xs font-medium text-app-text">تحديث إجباري</span>
              <span className="block text-[11px] text-app-muted-light">
                إلزام المتدربين بالتحديث للمتابعة
              </span>
            </div>
            <ToggleSwitch
              checked={isForceUpdate}
              onChange={setIsForceUpdate}
              aria-label="تحديث إجباري"
            />
          </div>

          <div className="flex items-center justify-between rounded-xl border border-app-line bg-app-card p-3">
            <div>
              <span className="block text-xs font-medium text-app-text">تفعيل الإصدار</span>
              <span className="block text-[11px] text-app-muted-light">
                متاح للتنزيل وإرسال إشعار FCM
              </span>
            </div>
            <ToggleSwitch
              checked={isActive}
              onChange={setIsActive}
              aria-label="تفعيل الإصدار"
            />
          </div>
        </div>

        {/* Release Notes */}
        <div>
          <label className="block text-xs font-medium text-app-text mb-1.5">
            ملاحظات الإصدار وسجل التغييرات
          </label>
          <textarea
            rows={3}
            placeholder="اكتب الميزات والتحسينات الجديدة في هذا الإصدار لتظهر للمتدربين..."
            value={releaseNotes}
            onChange={(e) => setReleaseNotes(e.target.value)}
            className="w-full rounded-xl border border-app-line bg-app-card px-3.5 py-2.5 text-sm text-app-text outline-none focus:border-app-yellow transition resize-none"
          />
        </div>

        {/* Actions */}
        <div className="flex items-center justify-end gap-3 pt-2 border-t border-app-line">
          <Button type="button" variant="outline" onClick={onClose} disabled={isSubmitting}>
            إلغاء
          </Button>
          <Button type="submit" variant="primary" loading={isSubmitting}>
            {isEditing ? "حفظ التعديلات" : "رفع ونشر الإصدار"}
          </Button>
        </div>
      </form>
    </Modal>
  );
}
