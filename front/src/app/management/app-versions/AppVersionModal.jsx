"use client";

import { useEffect, useRef, useState } from "react";
import { Field } from "@/components/forms/Field";
import { TextAreaField } from "@/components/forms/TextAreaField";
import {
  AndroidIcon,
  AppleIcon,
  DownloadIcon,
  FileUpIcon,
  TrashIcon,
} from "@/components/icons/Icons";
import Button from "@/components/ui/Button";
import Modal from "@/components/ui/Modal";
import ToggleSwitch from "@/components/ui/ToggleSwitch";

const MAX_FILE_SIZE = 150 * 1024 * 1024;

function ChoiceCard({ selected, onClick, icon: Icon, title, description, tone = "yellow" }) {
  const iconTone = tone === "green" ? "text-app-green" : "text-app-blue";

  return (
    <button
      type="button"
      onClick={onClick}
      aria-pressed={selected}
      className={`flex min-h-20 items-center gap-3 rounded-xl border p-3 text-start transition ${
        selected
          ? "border-app-yellow bg-app-yellow-soft ring-1 ring-app-yellow/30"
          : "border-app-line bg-app-card-soft hover:border-app-yellow/40 hover:bg-app-card-hover"
      }`}
    >
      <span
        className={`grid size-10 shrink-0 place-items-center rounded-lg bg-app-panel ${iconTone}`}
      >
        <Icon className="size-5" />
      </span>
      <span className="min-w-0">
        <span className="block text-sm font-medium text-app-text">{title}</span>
        <span className="mt-1 block text-[11px] text-app-muted-light">{description}</span>
      </span>
    </button>
  );
}

function SettingCard({ title, description, checked, onChange, disabled }) {
  return (
    <div className="flex items-center justify-between gap-4 rounded-xl border border-app-line bg-app-card-soft p-4">
      <div className="min-w-0 text-start">
        <p className="text-sm font-medium text-app-text">{title}</p>
        <p className="mt-1 text-[11px] leading-5 text-app-muted-light">{description}</p>
      </div>
      <ToggleSwitch
        checked={checked}
        onChange={(event) => onChange(event.target.checked)}
        disabled={disabled}
        ariaLabel={title}
      />
    </div>
  );
}

export default function AppVersionModal({
  open,
  onClose,
  initialData = null,
  onSubmit,
  isSubmitting = false,
}) {
  const isEditing = Boolean(initialData);
  const fileInputRef = useRef(null);
  const [platform, setPlatform] = useState("android");
  const [versionNumber, setVersionNumber] = useState("");
  const [buildNumber, setBuildNumber] = useState("1");
  const [sourceType, setSourceType] = useState("file");
  const [downloadUrl, setDownloadUrl] = useState("");
  const [file, setFile] = useState(null);
  const [isForceUpdate, setIsForceUpdate] = useState(false);
  const [isActive, setIsActive] = useState(true);
  const [releaseNotes, setReleaseNotes] = useState("");
  const [validationError, setValidationError] = useState("");

  useEffect(() => {
    if (!open) return;

    setPlatform(initialData?.platform || "android");
    setVersionNumber(initialData?.version_number || "");
    setBuildNumber(String(initialData?.build_number ?? "1"));
    setDownloadUrl(initialData?.download_url || "");
    setSourceType(initialData?.file_path || !initialData?.download_url ? "file" : "url");
    setFile(null);
    setIsForceUpdate(Boolean(initialData?.is_force_update));
    setIsActive(initialData?.is_active === undefined ? true : Boolean(initialData.is_active));
    setReleaseNotes(initialData?.release_notes || "");
    setValidationError("");

    if (fileInputRef.current) fileInputRef.current.value = "";
  }, [initialData, open]);

  function selectFile(selected) {
    if (!selected) return;

    if (selected.size > MAX_FILE_SIZE) {
      setFile(null);
      setValidationError("حجم الملف يتجاوز الحد المسموح به (150 ميغابايت).");
      if (fileInputRef.current) fileInputRef.current.value = "";
      return;
    }

    setFile(selected);
    setValidationError("");
  }

  function removeFile(event) {
    event.stopPropagation();
    setFile(null);
    if (fileInputRef.current) fileInputRef.current.value = "";
  }

  async function handleSubmit(event) {
    event.preventDefault();
    setValidationError("");

    if (!versionNumber.trim()) {
      setValidationError("يرجى إدخال رقم الإصدار، مثل 1.0.0.");
      return;
    }

    const buildNum = Number.parseInt(buildNumber, 10);
    if (!Number.isInteger(buildNum) || buildNum < 1) {
      setValidationError("يرجى إدخال رقم بناء صحيح أكبر من صفر.");
      return;
    }

    if (!isEditing && sourceType === "file" && !file) {
      setValidationError("يرجى اختيار ملف التطبيق للرفع.");
      return;
    }

    if (sourceType === "url" && !downloadUrl.trim()) {
      setValidationError("يرجى إدخال رابط تحميل مباشر.");
      return;
    }

    const formData = new FormData();
    formData.append("platform", platform);
    formData.append("version_number", versionNumber.trim());
    formData.append("build_number", String(buildNum));
    formData.append("is_force_update", isForceUpdate ? "1" : "0");
    formData.append("is_active", isActive ? "1" : "0");

    if (releaseNotes.trim()) formData.append("release_notes", releaseNotes.trim());
    if (sourceType === "file" && file) formData.append("app_file", file);
    if (sourceType === "url") formData.append("download_url", downloadUrl.trim());

    try {
      await onSubmit(formData);
    } catch {
      // The request hook displays the API error.
    }
  }

  return (
    <Modal
      open={open}
      onClose={isSubmitting ? undefined : onClose}
      title={isEditing ? "تعديل إصدار التطبيق" : "إضافة إصدار جديد"}
      subtitle="بيانات النسخة التي ستصبح متاحة لمستخدمي تطبيق المتدرب"
      className="max-w-2xl"
    >
      <form onSubmit={handleSubmit} className="space-y-6" noValidate>
        {validationError && (
          <div
            className="rounded-xl border border-app-red/25 bg-app-red/10 px-4 py-3 text-sm text-app-red"
            role="alert"
          >
            {validationError}
          </div>
        )}

        <section>
          <div className="mb-3 text-start">
            <h3 className="text-sm font-medium text-app-text">نظام التشغيل</h3>
            <p className="mt-1 text-xs text-app-muted-light">
              اختر المنصة التي ينتمي إليها الإصدار.
            </p>
          </div>
          <div className="grid gap-3 sm:grid-cols-2" role="group" aria-label="نظام التشغيل">
            <ChoiceCard
              selected={platform === "android"}
              onClick={() => setPlatform("android")}
              icon={AndroidIcon}
              title="Android"
              description="ملفات APK أو AAB"
              tone="green"
            />
            <ChoiceCard
              selected={platform === "ios"}
              onClick={() => setPlatform("ios")}
              icon={AppleIcon}
              title="iOS"
              description="تطبيقات أجهزة Apple"
              tone="blue"
            />
          </div>
        </section>

        <div className="grid gap-4 sm:grid-cols-2">
          <Field
            label="رقم الإصدار"
            value={versionNumber}
            onChange={(event) => setVersionNumber(event.target.value)}
            placeholder="مثال: 1.0.0"
            dir="ltr"
            disabled={isSubmitting}
          />
          <Field
            label="رقم البناء"
            type="number"
            min="1"
            value={buildNumber}
            onChange={(event) => setBuildNumber(event.target.value)}
            placeholder="مثال: 1"
            dir="ltr"
            disabled={isSubmitting}
          />
        </div>

        <section className="rounded-xl border border-app-line bg-app-panel-soft p-4">
          <div className="mb-4 text-start">
            <h3 className="text-sm font-medium text-app-text">مصدر ملف التطبيق</h3>
            <p className="mt-1 text-xs text-app-muted-light">
              ارفع الملف إلى النظام أو استخدم رابط تنزيل مباشر.
            </p>
          </div>

          <div className="grid grid-cols-2 gap-2 rounded-lg bg-app-card-soft p-1">
            <button
              type="button"
              onClick={() => setSourceType("file")}
              className={`rounded-lg px-3 py-2 text-xs font-medium transition ${
                sourceType === "file"
                  ? "bg-app-yellow text-app-on-accent shadow-sm"
                  : "text-app-muted-light hover:text-app-text"
              }`}
            >
              رفع ملف
            </button>
            <button
              type="button"
              onClick={() => setSourceType("url")}
              className={`rounded-lg px-3 py-2 text-xs font-medium transition ${
                sourceType === "url"
                  ? "bg-app-yellow text-app-on-accent shadow-sm"
                  : "text-app-muted-light hover:text-app-text"
              }`}
            >
              رابط مباشر
            </button>
          </div>

          {sourceType === "file" ? (
            <div className="relative mt-4">
              <input
                ref={fileInputRef}
                type="file"
                accept=".apk,.ipa,.aab,application/vnd.android.package-archive"
                onChange={(event) => selectFile(event.target.files?.[0])}
                className="sr-only"
                disabled={isSubmitting}
              />
              <button
                type="button"
                onClick={() => fileInputRef.current?.click()}
                disabled={isSubmitting}
                className={`group flex min-h-32 w-full flex-col items-center justify-center rounded-xl border border-dashed border-app-muted/40 bg-app-card-soft px-5 py-6 text-center transition hover:border-app-yellow/60 hover:bg-app-yellow-soft/40 disabled:opacity-60 ${file ? "pb-14" : ""}`}
              >
                <span className="grid size-11 place-items-center rounded-xl bg-app-yellow-soft text-app-yellow transition group-hover:-translate-y-0.5">
                  <FileUpIcon className="size-5" />
                </span>
                <span className="mt-3 max-w-full truncate text-sm font-medium text-app-text">
                  {file ? file.name : "اضغط لاختيار ملف التطبيق"}
                </span>
                <span className="mt-1 text-[11px] text-app-muted-light">
                  {file
                    ? `${(file.size / (1024 * 1024)).toFixed(2)} MB`
                    : isEditing && initialData?.download_url
                      ? "اتركه دون تغيير للاحتفاظ بالملف الحالي"
                      : "APK أو AAB أو IPA — بحد أقصى 150 MB"}
                </span>
              </button>
              {file && (
                <button
                  type="button"
                  className="absolute bottom-3 start-1/2 inline-flex -translate-x-1/2 items-center gap-1.5 rounded-lg bg-app-red/10 px-2.5 py-1.5 text-xs text-app-red transition hover:bg-app-red/20"
                  onClick={removeFile}
                  disabled={isSubmitting}
                >
                  <TrashIcon className="size-3.5" />
                  إزالة الملف
                </button>
              )}
            </div>
          ) : (
            <Field
              className="mt-4"
              label="رابط التحميل المباشر"
              type="url"
              value={downloadUrl}
              onChange={(event) => setDownloadUrl(event.target.value)}
              placeholder="https://example.com/app-release.apk"
              icon={DownloadIcon}
              dir="ltr"
              disabled={isSubmitting}
            />
          )}
        </section>

        <div className="grid gap-3 sm:grid-cols-2">
          <SettingCard
            title="تحديث إجباري"
            description="إلزام المتدربين بتثبيت هذه النسخة قبل المتابعة."
            checked={isForceUpdate}
            onChange={setIsForceUpdate}
            disabled={isSubmitting}
          />
          <SettingCard
            title="إصدار نشط"
            description="إتاحة النسخة للتنزيل والاستخدام مباشرة."
            checked={isActive}
            onChange={setIsActive}
            disabled={isSubmitting}
          />
        </div>

        <TextAreaField
          label="ملاحظات الإصدار"
          value={releaseNotes}
          onChange={(event) => setReleaseNotes(event.target.value)}
          placeholder="اكتب أبرز الميزات والتحسينات والإصلاحات في هذا الإصدار..."
          disabled={isSubmitting}
        />

        <div className="flex flex-col-reverse gap-3 border-t border-app-line pt-5 sm:flex-row sm:justify-end">
          <Button
            type="button"
            tone="outline"
            className="sm:min-w-28"
            onClick={onClose}
            disabled={isSubmitting}
          >
            إلغاء
          </Button>
          <Button
            type="submit"
            className="text-black sm:min-w-36"
            loading={isSubmitting}
            loadingLabel="جاري حفظ الإصدار"
          >
            {isEditing ? "حفظ التعديلات" : "حفظ ونشر الإصدار"}
          </Button>
        </div>
      </form>
    </Modal>
  );
}
