"use client";

import { useEffect, useRef } from "react";
import Button from "@/components/ui/Button";
import Dropdown from "@/components/ui/Dropdown";
import ToggleSwitch from "@/components/ui/ToggleSwitch";
import { ATTENDANCE_SCAN_MODES } from "./attendanceConstants";
import { CameraIcon, QrCodeIcon, StopIcon } from "./AttendanceIcons";

const QR_READER_ID = "attendance-qr-reader";

/**
 * Starts and disposes the browser camera scanner for the current component.
 */
async function stopQrScanner(scanner) {
  if (!scanner) return;

  try {
    if (scanner.isScanning) {
      await scanner.stop();
    }
    scanner.clear();
  } catch {
    // قد يكون المتصفح قد أغلق مسار الكاميرا قبل اكتمال التنظيف.
  }
}

/**
 * Renders the QR scanner controls and owns the browser camera lifecycle.
 */
export default function AttendanceScanner({
  alwaysOn,
  scannerActive,
  scanMode,
  branchId,
  branchOptions,
  isProcessing,
  onScanModeChange,
  onBranchChange,
  onAlwaysOnChange,
  onScanClick,
  onScanSuccess,
  onScannerError,
  onStop,
}) {
  const onScanSuccessRef = useRef(onScanSuccess);
  const onScannerErrorRef = useRef(onScannerError);
  const scanLockedRef = useRef(false);
  const lastScanRef = useRef({ value: "", timestamp: 0 });

  useEffect(() => {
    onScanSuccessRef.current = onScanSuccess;
    onScannerErrorRef.current = onScannerError;
  }, [onScanSuccess, onScannerError]);

  useEffect(() => {
    if (!scannerActive) return undefined;

    let scanner;
    let disposed = false;

    /**
     * Loads the camera library only when scanning is requested.
     */
    async function startScanner() {
      try {
        const { Html5Qrcode } = await import("html5-qrcode");
        if (disposed) return;

        scanner = new Html5Qrcode(QR_READER_ID);
        await scanner.start(
          { facingMode: "environment" },
          { fps: 10, qrbox: { width: 250, height: 250 } },
          (decodedText) => {
            if (scanLockedRef.current) return;

            const now = Date.now();
            const isRepeatedScan =
              lastScanRef.current.value === decodedText &&
              now - lastScanRef.current.timestamp < 3000;
            if (isRepeatedScan) return;

            lastScanRef.current = { value: decodedText, timestamp: now };
            scanLockedRef.current = true;
            Promise.resolve(onScanSuccessRef.current?.(decodedText)).finally(() => {
              scanLockedRef.current = false;
            });
          },
          () => {
            // عدم العثور على رمز في الإطار الحالي حالة طبيعية أثناء المسح.
          },
        );

        if (disposed) {
          await stopQrScanner(scanner);
        }
      } catch (error) {
        if (!disposed) {
          onScannerErrorRef.current?.(error);
        }
      }
    }

    void startScanner();

    return () => {
      disposed = true;
      scanLockedRef.current = false;
      void stopQrScanner(scanner);
    };
  }, [scannerActive]);

  const isCheckIn = scanMode === ATTENDANCE_SCAN_MODES.CHECK_IN;
  const canActivate = !scannerActive && !isProcessing;

  /**
   * Makes the scanner activation surface accessible from the keyboard.
   */
  function handleActivationKeyDown(event) {
    if (!canActivate) return;
    if (event.key === "Enter" || event.key === " ") {
      event.preventDefault();
      onScanClick();
    }
  }

  return (
    <section
      className="w-full max-w-[520px] overflow-hidden rounded-xl border border-app-line bg-app-yellow text-[#1b1b1b]"
      dir="rtl"
    >
      <div className="flex flex-col gap-3 p-3 sm:flex-row sm:items-start sm:justify-between">
        <div className="flex items-center justify-start gap-2 text-right">
          <div>
            <div className="flex items-center gap-2">
              <QrCodeIcon className="size-5 shrink-0" />
              <h2 className="text-lg font-medium">قارئ QR</h2>
            </div>
            <p className="mt-1 text-xs text-[#4b4b4b]">
              {isCheckIn
                ? "قم بمسح بطاقة العضو لتسجيل الحضور"
                : "قم بمسح بطاقة العضو لتسجيل الخروج"}
            </p>
          </div>
        </div>

        <div className="flex items-center justify-start gap-2 text-[#1b1b1b]">
          <span
            className={`rounded-lg px-2.5 py-1 text-[11px] font-medium ${
              scannerActive ? "bg-black/20 text-black" : "bg-white/35 text-[#4b4b4b]"
            }`}
          >
            {isProcessing ? "جاري تسجيل الحركة" : scannerActive ? "الماسح يعمل" : "جاهز للمسح"}
          </span>
          <span className="text-xs font-medium">تشغيل دائم</span>
          <ToggleSwitch
            checked={alwaysOn}
            onChange={(event) => onAlwaysOnChange(event.target.checked)}
            size="sm"
            disabled={isProcessing}
          />
        </div>
      </div>

      <div className="space-y-3 px-3 pb-3">
        <div className="flex rounded-lg bg-black/10 p-1">
          <button
            type="button"
            onClick={() => onScanModeChange(ATTENDANCE_SCAN_MODES.CHECK_IN)}
            disabled={isProcessing}
            className={`flex-1 rounded-md py-1.5 text-xs font-medium transition-all disabled:opacity-60 ${
              isCheckIn
                ? "bg-white font-bold text-black shadow-sm"
                : "text-[#4b4b4b] hover:text-[#1b1b1b]"
            }`}
          >
            تسجيل دخول
          </button>
          <button
            type="button"
            onClick={() => onScanModeChange(ATTENDANCE_SCAN_MODES.CHECK_OUT)}
            disabled={isProcessing}
            className={`flex-1 rounded-md py-1.5 text-xs font-medium transition-all disabled:opacity-60 ${
              !isCheckIn
                ? "bg-white font-bold text-black shadow-sm"
                : "text-[#4b4b4b] hover:text-[#1b1b1b]"
            }`}
          >
            تسجيل خروج
          </button>
        </div>

        {isCheckIn && (
          <label className="block text-right text-xs font-medium text-[#303030]">
            الفرع
            <Dropdown
              className="mt-1.5 text-white"
              buttonClassName="h-10 border border-black/20 bg-black/15"
              menuClassName="text-app-text"
              value={branchId}
              options={branchOptions}
              onChange={onBranchChange}
              placeholder="اختر الفرع"
              disabled={scannerActive || isProcessing}
            />
          </label>
        )}
      </div>

      <div
        role="button"
        tabIndex={canActivate ? 0 : -1}
        aria-label="تشغيل قارئ رمز QR"
        aria-disabled={!canActivate}
        onClick={canActivate ? onScanClick : undefined}
        onKeyDown={handleActivationKeyDown}
        className={`group mx-3 mb-3 grid min-h-[145px] w-[calc(100%-1.5rem)] place-items-center overflow-hidden rounded-xl border border-dashed border-white/70 bg-[#957a04] text-[#1b1b1b] transition ${
          canActivate ? "cursor-pointer hover:bg-[#8a7104]" : "cursor-default opacity-80"
        }`}
      >
        <div className="relative flex w-full flex-col items-center justify-center">
          <div
            id={QR_READER_ID}
            className={scannerActive ? "w-full max-w-[300px] overflow-hidden rounded-lg" : "hidden"}
          />

          {!scannerActive && (
            <div className="flex flex-col items-center">
              <CameraIcon className="size-12 transition group-hover:scale-105" />
              <span className="mt-3 text-base font-medium">انقر لتفعيل قارئ QR</span>
              <span className="mt-1 text-xs">
                {alwaysOn ? "سيبقى المسح فعالاً بشكل دائم" : "سيتم المسح عند النقر"}
              </span>
            </div>
          )}
        </div>
      </div>

      {scannerActive && !alwaysOn && (
        <div className="flex justify-end px-3 pb-3">
          <Button
            type="button"
            tone="dark"
            className="h-9 px-3 text-xs"
            icon={<StopIcon />}
            onClick={onStop}
            disabled={isProcessing}
          >
            إيقاف المسح
          </Button>
        </div>
      )}
    </section>
  );
}
