"use client";

import { useEffect, useMemo, useRef, useState } from "react";
import { createPortal } from "react-dom";
import { ClockIcon as Clock, XIcon as X, TagIcon } from "@/components/icons/Icons";
import { useTimeFormat } from "@/lib/TimeFormatContext";
import {
  applyTimeMask as applyMask,
  cleanTimeInput as cleanTyped,
  padTimeSegment,
  parseTimeInput as parseTyped,
} from "@/components/forms/timePickerUtils";
//test
export default function TimePickerSmart({
  label,
  value,
  onChange,
  placeholder = "HH:MM",
  required = false,
  disabled = false,
  allowClear = true,
  autoDefault = false,
  error,
}) {
  const { timeFormat, formatTime } = useTimeFormat();
  const inputWrapRef = useRef(null);
  const dropdownRef = useRef(null);

  const hoursRef = useRef(null);
  const minutesRef = useRef(null);

  const [mounted, setMounted] = useState(false);
  const [open, setOpen] = useState(false);

  const [pos, setPos] = useState({
    top: 0,
    left: 0,
    width: 280,
    placement: "bottom",
  });

  const [digits, setDigits] = useState(() => {
    if (value) return cleanTyped(value);
    if (autoDefault) {
      const now = new Date();
      return cleanTyped(`${padTimeSegment(now.getHours())}:${padTimeSegment(now.getMinutes())}`);
    }
    return "";
  });

  const inputText = useMemo(() => {
    if (open) {
      return applyMask(digits);
    }
    return formatTime(value);
  }, [value, digits, open, formatTime]);

  // internal state for the pickers
  const [viewTime, setViewTime] = useState({ hour: 12, minute: 0 });
  const [period, setPeriod] = useState("ص"); // "ص" or "م"

  useEffect(() => {
    setMounted(true);
    if (autoDefault && !value && onChange) {
      const now = new Date();
      onChange(
        `${String(now.getHours()).padStart(2, "0")}:${String(now.getMinutes()).padStart(2, "0")}`,
      );
    }
  }, []);

  // Sync when closed
  useEffect(() => {
    if (!open) {
      setDigits(cleanTyped(value));
    }
  }, [value, open]);

  // Sync when opening
  useEffect(() => {
    if (open) {
      setDigits(cleanTyped(value));
      if (value) {
        const parsed = parseTyped(value);
        if (parsed) {
          const [hStr, mStr] = parsed.split(":");
          const h24 = Number(hStr);
          const m = Number(mStr);
          if (timeFormat === "12") {
            const isPm = h24 >= 12;
            const h12 = h24 % 12 === 0 ? 12 : h24 % 12;
            setViewTime({ hour: h12, minute: m });
            setPeriod(isPm ? "م" : "ص");
          } else {
            setViewTime({ hour: h24, minute: m });
          }
        }
      } else {
        const now = new Date();
        const h24 = now.getHours();
        const m = Math.floor(now.getMinutes() / 5) * 5;
        if (timeFormat === "12") {
          const isPm = h24 >= 12;
          const h12 = h24 % 12 === 0 ? 12 : h24 % 12;
          setViewTime({ hour: h12, minute: m });
          setPeriod(isPm ? "م" : "ص");
        } else {
          setViewTime({ hour: h24, minute: m });
        }
      }
    }
  }, [open, value, timeFormat]);

  const updatePosition = () => {
    if (!inputWrapRef.current) return;

    const rect = inputWrapRef.current.getBoundingClientRect();
    const margin = 10;

    const desiredW = timeFormat === "12" ? 280 : 220; // wider width for time picker with period column
    const width = Math.min(desiredW, window.innerWidth - margin * 2);

    const estimatedH = dropdownRef.current?.offsetHeight || 260;

    const spaceBelow = window.innerHeight - rect.bottom;
    const spaceAbove = rect.top;
    const shouldFlip = spaceBelow < estimatedH && spaceAbove > spaceBelow;

    let top = shouldFlip ? rect.top - estimatedH - 8 : rect.bottom + 8;

    // In RTL, align to the physical right of the input (which is the logical start)
    let left = rect.right - width;

    if (left + width > window.innerWidth - margin) left = window.innerWidth - width - margin;
    if (left < margin) left = margin;

    if (top < margin) top = margin;
    if (top + estimatedH > window.innerHeight - margin)
      top = window.innerHeight - estimatedH - margin;

    setPos({ top, left, width, placement: shouldFlip ? "top" : "bottom" });
  };

  useEffect(() => {
    if (!open) return;

    updatePosition();
    const onResize = () => updatePosition();
    const onScroll = () => updatePosition();

    window.addEventListener("resize", onResize);
    window.addEventListener("scroll", onScroll, true);

    return () => {
      window.removeEventListener("resize", onResize);
      window.removeEventListener("scroll", onScroll, true);
    };
  }, [open, timeFormat]);

  useEffect(() => {
    if (!open) return;
    const onKey = (e) => {
      if (e.key === "Escape") setOpen(false);
    };
    document.addEventListener("keydown", onKey);
    return () => document.removeEventListener("keydown", onKey);
  }, [open]);

  // Scroll to active time on open
  useEffect(() => {
    if (!open) return;
    requestAnimationFrame(() => {
      if (hoursRef.current) {
        const offset =
          timeFormat === "12" ? (viewTime.hour - 1) * 36 - 80 : viewTime.hour * 36 - 80;
        hoursRef.current.scrollTop = offset;
      }
      if (minutesRef.current) {
        minutesRef.current.scrollTop = viewTime.minute * 36 - 80;
      }
    });
  }, [open, viewTime.hour, viewTime.minute, timeFormat]);

  const commitTyped = () => {
    const time = parseTyped(inputText);
    if (time) {
      onChange?.(time);
      return;
    }
    if (!inputText) {
      onChange?.("");
      setDigits("");
    }
  };

  const pickTime = (h, m, p) => {
    let finalH = h;
    if (timeFormat === "12") {
      if (p === "م") {
        if (h < 12) finalH += 12;
      } else {
        if (h === 12) finalH = 0;
      }
    }
    const timeStr = `${padTimeSegment(finalH)}:${padTimeSegment(m)}`;
    onChange?.(timeStr);
    setDigits(cleanTyped(timeStr));
    setOpen(false);
  };

  const handleInputChange = (e) => {
    let d = cleanTyped(e.target.value);

    // Auto validate hours
    if (d.length >= 2) {
      const h = Number(d.slice(0, 2));
      if (h > 23) {
        d = "23" + d.slice(2);
      }
    }
    // Auto validate minutes
    if (d.length === 4) {
      const m = Number(d.slice(2, 4));
      if (m > 59) {
        d = d.slice(0, 2) + "59";
      }
    }

    setDigits(d);
    setOpen(true);
  };

  const clear = (e) => {
    e?.stopPropagation?.();
    onChange?.("");
    setDigits("");
    setOpen(false);
  };

  const hoursList =
    timeFormat === "12"
      ? Array.from({ length: 12 }, (_, i) => i + 1)
      : Array.from({ length: 24 }, (_, i) => i);
  const minutesList = Array.from({ length: 60 }, (_, i) => i);

  return (
    <div className="flex flex-col w-full text-start">
      {label && (
        <span className="mb-3 flex items-center gap-2 text-base font-medium text-app-text">
          <TagIcon className="size-4 shrink-0 text-app-yellow" />
          <span>{label}</span>
          {required ? <span className="text-app-red">*</span> : null}
        </span>
      )}

      {/* Input */}
      <div
        ref={inputWrapRef}
        className={[
          "relative flex h-[46px] w-full items-center justify-between rounded-lg px-4 text-sm transition outline-none",
          error
            ? "border border-app-red bg-app-red/5 focus-within:border-app-red focus-within:ring-1 focus-within:ring-app-red"
            : "border border-app-muted/50 bg-app-panel-soft/40 focus-within:border-app-yellow focus-within:ring-1 focus-within:ring-app-yellow",
          disabled ? "opacity-60 pointer-events-none" : "cursor-text",
        ].join(" ")}
        onClick={() => {
          if (disabled) return;
          setOpen(true);
          updatePosition();
        }}
        dir="ltr"
      >
        <button
          type="button"
          onClick={(e) => {
            e.stopPropagation();
            setOpen((v) => !v);
            setTimeout(updatePosition, 0);
          }}
          className="absolute left-4 top-1/2 -translate-y-1/2 text-app-muted-light hover:text-app-text transition-colors"
          title="اختيار الوقت"
        >
          <Clock size={18} />
        </button>

        <input
          value={inputText}
          onChange={handleInputChange}
          onFocus={() => {
            setOpen(true);
            updatePosition();
          }}
          onBlur={() => {
            commitTyped();
          }}
          onKeyDown={(e) => {
            if (e.key === "Enter") {
              commitTyped();
              setOpen(false);
            }
          }}
          placeholder={placeholder}
          className="w-full bg-transparent outline-none pl-8 pr-8 text-left text-app-text placeholder-app-muted-light tracking-widest"
          dir="ltr"
          inputMode="numeric"
          aria-invalid={Boolean(error)}
        />

        {allowClear && !!value && (
          <button
            type="button"
            onClick={clear}
            className="absolute right-4 top-1/2 -translate-y-1/2 text-app-muted-light hover:text-app-text transition-colors z-10"
            title="مسح"
          >
            <X size={16} />
          </button>
        )}
      </div>

      {/* Dropdown (Portal) */}
      {mounted &&
        open &&
        createPortal(
          <>
            <div
              className="fixed inset-0 z-[9998]"
              onMouseDown={() => {
                commitTyped();
                setOpen(false);
              }}
            />

            <div
              ref={dropdownRef}
              style={{
                top: pos.top,
                left: pos.left,
                width: pos.width,
                position: "fixed",
              }}
              className="z-[9999]"
              onMouseDown={(e) => e.preventDefault()}
            >
              <div className="bg-app-card border border-app-line rounded-2xl shadow-xl p-3 flex flex-col gap-3">
                <div
                  className="flex justify-between items-center px-2 text-app-text font-medium border-b border-app-line pb-2"
                  dir="rtl"
                >
                  <span>الساعات</span>
                  <span>الدقائق</span>
                  {timeFormat === "12" && <span>الفترة</span>}
                </div>

                <div className="flex justify-between h-[200px]" dir="rtl">
                  {/* Hours */}
                  <div
                    ref={hoursRef}
                    className="flex-1 overflow-y-auto scrollbar-hidden border-l border-app-line/50 pl-1 space-y-1"
                  >
                    {hoursList.map((h) => {
                      const isActive = h === viewTime.hour;
                      return (
                        <button
                          key={`h-${h}`}
                          type="button"
                          onClick={() => setViewTime((prev) => ({ ...prev, hour: h }))}
                          className={[
                            "w-full h-9 rounded-lg text-sm flex items-center justify-center transition-colors",
                            isActive
                              ? "bg-app-yellow text-app-bg font-bold"
                              : "text-app-text hover:bg-app-card-soft",
                          ].join(" ")}
                        >
                          {padTimeSegment(h)}
                        </button>
                      );
                    })}
                  </div>

                  {/* Minutes */}
                  <div
                    ref={minutesRef}
                    className="flex-1 overflow-y-auto scrollbar-hidden border-l border-app-line/50 px-1 space-y-1"
                  >
                    {minutesList.map((m) => {
                      const isActive = m === viewTime.minute;
                      return (
                        <button
                          key={`m-${m}`}
                          type="button"
                          onClick={() => setViewTime((prev) => ({ ...prev, minute: m }))}
                          className={[
                            "w-full h-9 rounded-lg text-sm flex items-center justify-center transition-colors",
                            isActive
                              ? "bg-app-yellow text-app-bg font-bold"
                              : "text-app-text hover:bg-app-card-soft",
                          ].join(" ")}
                        >
                          {padTimeSegment(m)}
                        </button>
                      );
                    })}
                  </div>

                  {/* Period Column (AM/PM) */}
                  {timeFormat === "12" && (
                    <div className="flex-1 px-1 flex flex-col justify-center gap-2">
                      <button
                        type="button"
                        onClick={() => setPeriod("ص")}
                        className={[
                          "w-full h-9 rounded-lg text-xs flex items-center justify-center transition-colors",
                          period === "ص"
                            ? "bg-app-yellow text-app-bg font-bold"
                            : "text-app-text hover:bg-app-card-soft",
                        ].join(" ")}
                      >
                        صباحاً (ص)
                      </button>
                      <button
                        type="button"
                        onClick={() => setPeriod("م")}
                        className={[
                          "w-full h-9 rounded-lg text-xs flex items-center justify-center transition-colors",
                          period === "م"
                            ? "bg-app-yellow text-app-bg font-bold"
                            : "text-app-text hover:bg-app-card-soft",
                        ].join(" ")}
                      >
                        مساءً (م)
                      </button>
                    </div>
                  )}
                </div>

                <div className="pt-2 border-t border-app-line">
                  <button
                    type="button"
                    onClick={() => pickTime(viewTime.hour, viewTime.minute, period)}
                    className="w-full bg-app-yellow text-app-bg rounded-lg py-2 text-sm font-medium hover:bg-yellow-400 transition"
                  >
                    تأكيد الوقت
                  </button>
                </div>
              </div>
            </div>
          </>,
          document.body,
        )}
      {error && (
        <span className="mt-1.5 block w-full text-right text-xs text-app-red" role="alert">
          {error}
        </span>
      )}
    </div>
  );
}
