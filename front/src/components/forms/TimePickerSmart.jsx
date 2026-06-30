"use client";

import { useEffect, useMemo, useRef, useState } from "react";
import { createPortal } from "react-dom";
import { ClockIcon as Clock, XIcon as X, TagIcon } from "@/components/icons/Icons";

function pad2(n) {
  return String(n).padStart(2, "0");
}

function cleanTyped(raw) {
  return String(raw || "")
    .replace(/\D/g, "")
    .slice(0, 4);
}

function applyMask(digits) {
  const h = digits.slice(0, 2);
  const m = digits.slice(2, 4);
  if (digits.length <= 2) return h;
  return `${h}:${m}`;
}

function parseTyped(masked) {
  const m = String(masked || "")
    .trim()
    .match(/^(\d{2}):(\d{2})$/);
  if (!m) return null;

  const hh = Number(m[1]);
  const mm = Number(m[2]);

  if (hh < 0 || hh > 23) return null;
  if (mm < 0 || mm > 59) return null;

  return `${pad2(hh)}:${pad2(mm)}`;
}

export default function TimePickerSmart({
  label,
  value,
  onChange,
  placeholder = "HH:MM",
  required = false,
  disabled = false,
  allowClear = true,
  autoDefault = true,
}) {
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

  const [digits, setDigits] = useState("");

  const inputText = useMemo(() => applyMask(digits), [digits]);

  // internal state for the pickers
  const [viewTime, setViewTime] = useState({ hour: 12, minute: 0 });

  useEffect(() => {
    setMounted(true);
    if (autoDefault && !value && onChange) {
      const now = new Date();
      onChange(`${pad2(now.getHours())}:${pad2(now.getMinutes())}`);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
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
          const [h, m] = parsed.split(":");
          setViewTime({ hour: Number(h), minute: Number(m) });
        }
      } else {
        const now = new Date();
        setViewTime({ hour: now.getHours(), minute: Math.floor(now.getMinutes() / 5) * 5 });
      }
    }
  }, [open, value]);

  const updatePosition = () => {
    if (!inputWrapRef.current) return;

    const rect = inputWrapRef.current.getBoundingClientRect();
    const margin = 10;

    const desiredW = 220; // smaller width for time picker
    const width = Math.min(desiredW, window.innerWidth - margin * 2);

    const estimatedH = dropdownRef.current?.offsetHeight || 260;

    const spaceBelow = window.innerHeight - rect.bottom;
    const spaceAbove = rect.top;
    const shouldFlip = spaceBelow < estimatedH && spaceAbove > spaceBelow;

    let top = shouldFlip ? rect.top - estimatedH - 8 : rect.bottom + 8;
    
    // In RTL, we might want to align to the physical right of the input (which is the logical start)
    // Assuming rect.right is the physical right.
    let left = rect.right - width; 

    if (left + width > window.innerWidth - margin)
      left = window.innerWidth - width - margin;
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
  }, [open]);

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
        hoursRef.current.scrollTop = viewTime.hour * 36 - 80;
      }
      if (minutesRef.current) {
        minutesRef.current.scrollTop = viewTime.minute * 36 - 80;
      }
    });
  }, [open, viewTime.hour, viewTime.minute]);

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

  const pickTime = (h, m) => {
    const timeStr = `${pad2(h)}:${pad2(m)}`;
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

  const hoursList = Array.from({ length: 24 }, (_, i) => i);
  const minutesList = Array.from({ length: 60 }, (_, i) => i);

  return (
    <div className="flex flex-col w-full text-start">
      {label && (
        <span className="mb-3 flex items-center gap-2 text-base font-medium text-white">
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
          "border border-app-muted/50 bg-app-panel-soft/40 focus-within:border-app-yellow focus-within:ring-1 focus-within:ring-app-yellow",
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
          className="absolute left-4 top-1/2 -translate-y-1/2 text-app-muted-light hover:text-white transition-colors"
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
          className="w-full bg-transparent outline-none pl-8 pr-8 text-left text-white placeholder-app-muted-light tracking-widest"
          dir="ltr"
          inputMode="numeric"
        />

        {allowClear && !!value && (
          <button
            type="button"
            onClick={clear}
            className="absolute right-4 top-1/2 -translate-y-1/2 text-app-muted-light hover:text-white transition-colors z-10"
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
                <div className="flex justify-between items-center px-2 text-white font-medium border-b border-app-line pb-2" dir="rtl">
                  <span>الساعات</span>
                  <span>الدقائق</span>
                </div>
                
                <div className="flex justify-between h-[200px]" dir="ltr">
                  {/* Hours */}
                  <div ref={hoursRef} className="flex-1 overflow-y-auto scrollbar-hidden border-r border-app-line/50 pr-1 space-y-1">
                    {hoursList.map((h) => {
                      const isActive = h === viewTime.hour;
                      return (
                        <button
                          key={`h-${h}`}
                          type="button"
                          onClick={() => setViewTime((prev) => ({ ...prev, hour: h }))}
                          className={[
                            "w-full h-9 rounded-lg text-sm flex items-center justify-center transition-colors",
                            isActive ? "bg-app-yellow text-app-bg font-bold" : "text-app-text hover:bg-app-card-soft"
                          ].join(" ")}
                        >
                          {pad2(h)}
                        </button>
                      )
                    })}
                  </div>

                  {/* Minutes */}
                  <div ref={minutesRef} className="flex-1 overflow-y-auto scrollbar-hidden pl-1 space-y-1">
                    {minutesList.map((m) => {
                      const isActive = m === viewTime.minute;
                      return (
                        <button
                          key={`m-${m}`}
                          type="button"
                          onClick={() => setViewTime((prev) => ({ ...prev, minute: m }))}
                          className={[
                            "w-full h-9 rounded-lg text-sm flex items-center justify-center transition-colors",
                            isActive ? "bg-app-yellow text-app-bg font-bold" : "text-app-text hover:bg-app-card-soft"
                          ].join(" ")}
                        >
                          {pad2(m)}
                        </button>
                      )
                    })}
                  </div>
                </div>

                <div className="pt-2 border-t border-app-line">
                  <button 
                    type="button"
                    onClick={() => pickTime(viewTime.hour, viewTime.minute)}
                    className="w-full bg-app-yellow text-app-bg rounded-lg py-2 text-sm font-medium hover:bg-yellow-400 transition"
                  >
                    تأكيد الوقت
                  </button>
                </div>
              </div>
            </div>
          </>,
          document.body
        )}
    </div>
  );
}
