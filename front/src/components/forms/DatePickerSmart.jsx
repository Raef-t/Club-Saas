"use client";

import { useEffect, useMemo, useRef, useState } from "react";
import { createPortal } from "react-dom";
import {
  CalendarIcon,
  ChevronLeft,
  ChevronRight,
  ChevronDownIcon as ChevronDown,
  XIcon as X,
  TagIcon,
} from "@/components/icons/Icons";
import {
  applyDateMask as applyMask,
  cleanDateInput as cleanTyped,
  formatDateDisplay as formatDisplay,
  fromIsoDate as fromISO,
  parseDateInput as parseTyped,
  toIsoDate as toISO,
} from "@/components/forms/datePickerUtils";

export const MONTHS = [
  "كانون الثاني",
  "شباط",
  "آذار",
  "نيسان",
  "أيار",
  "حزيران",
  "تموز",
  "آب",
  "أيلول",
  "تشرين الأول",
  "تشرين الثاني",
  "كانون الأول",
];

const WEEKDAYS = ["الأحد", "الاثنين", "الثلاثاء", "الأربعاء", "الخميس", "الجمعة", "السبت"];

export default function DatePickerSmart({
  label,
  value,
  onChange,
  placeholder,
  format = "DD/MM/YYYY",
  required = false,
  disabled = false,
  allowClear = true,
  autoDefault = false,
  compact = false,
  error,
  minYear = 1940,
  maxYear = 2050,
}) {
  const inputWrapRef = useRef(null);
  const dropdownRef = useRef(null);
  const yearListRef = useRef(null);

  const [mounted, setMounted] = useState(false);
  const [open, setOpen] = useState(false);

  // day | month | year
  const [mode, setMode] = useState("day");

  const [pos, setPos] = useState({
    top: 0,
    left: 0,
    width: 295,
    placement: "bottom",
  });

  const [digits, setDigits] = useState(() => {
    if (value) return cleanTyped(value);
    if (autoDefault) return cleanTyped(formatDisplay(toISO(new Date()), format));
    return "";
  });

  const inputText = useMemo(() => applyMask(digits, format), [digits, format]);

  const selectedDate = useMemo(() => fromISO(value), [value]);
  const [view, setView] = useState(() => selectedDate || new Date());

  useEffect(() => {
    setMounted(true);
    if (autoDefault && !value && onChange) {
      onChange(toISO(new Date()));
    }
  }, []);

  // Sync when closed (Background format)
  useEffect(() => {
    if (!open) {
      const txt = formatDisplay(value, format);
      setDigits(cleanTyped(txt));
    }
  }, [value, format, open]);

  // Sync when opening (Keep standard format)
  useEffect(() => {
    if (open) {
      if (value) {
        const formatted = formatDisplay(value, format);
        setDigits(cleanTyped(formatted));
      } else {
        setDigits(""); // يبقى فارغاً إذا لم تكن هناك قيمة
      }
      setMode("day");
      setView(selectedDate || new Date());
    }
  }, [open, selectedDate, value, format]);

  const updatePosition = () => {
    if (!inputWrapRef.current) return;

    const rect = inputWrapRef.current.getBoundingClientRect();
    const margin = 10;

    const desiredW = 295;
    const width = Math.min(desiredW, window.innerWidth - margin * 2);

    const estimatedH = dropdownRef.current?.offsetHeight || 330;

    const spaceBelow = window.innerHeight - rect.bottom;
    const spaceAbove = rect.top;
    const shouldFlip = spaceBelow < estimatedH && spaceAbove > spaceBelow;

    let top = shouldFlip ? rect.top - estimatedH - 8 : rect.bottom + 8;
    let left = rect.right - width; // align right for RTL

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
  }, [open]);

  // ESC close
  useEffect(() => {
    if (!open) return;
    const onKey = (e) => {
      if (e.key === "Escape") setOpen(false);
    };
    document.addEventListener("keydown", onKey);
    return () => document.removeEventListener("keydown", onKey);
  }, [open]);

  const selectedISO = value || "";
  const todayISO = toISO(new Date());
  const activeISO = selectedISO || todayISO;

  const daysGrid = useMemo(() => {
    const y = view.getFullYear();
    const m = view.getMonth();

    const firstDay = new Date(y, m, 1).getDay();
    const daysInMonth = new Date(y, m + 1, 0).getDate();

    const cells = [];
    for (let i = 0; i < firstDay; i++) cells.push({ date: null, inMonth: false });

    for (let d = 1; d <= daysInMonth; d++) {
      cells.push({ date: new Date(y, m, d), inMonth: true });
    }

    while (cells.length < 42) cells.push({ date: null, inMonth: false });

    return cells;
  }, [view]);

  const weeks = useMemo(() => {
    const out = [];
    for (let i = 0; i < 6; i++) out.push(daysGrid.slice(i * 7, i * 7 + 7));
    return out;
  }, [daysGrid]);

  const commitTyped = () => {
    const iso = parseTyped(inputText, format);
    if (iso) {
      onChange?.(iso);
      const dt = fromISO(iso);
      if (dt) setView(dt);
      return;
    }
    if (!inputText) {
      onChange?.("");
      setDigits("");
    }
  };

  const pickDate = (dt) => {
    const iso = toISO(dt);
    onChange?.(iso);
    // After picking, sync to current format
    setDigits(cleanTyped(formatDisplay(iso, format)));
    setOpen(false);
  };

  const goPrevMonth = () => setView((v) => new Date(v.getFullYear(), v.getMonth() - 1, 1));
  const goNextMonth = () => setView((v) => new Date(v.getFullYear(), v.getMonth() + 1, 1));

  const handleInputChange = (e) => {
    let d = cleanTyped(e.target.value);
    const isAdding = d.length > digits.length;

    // Auto-zero logic for DD/MM/YYYY or MM/DD/YYYY
    if (open) {
      if (format === "MM/DD/YYYY") {
        // Month start (index 0)
        if (d.length === 1) {
          const m1 = d[0];
          if (m1 > "1") d = "0" + m1;
        }
        // Day start (index 2)
        if (d.length === 3) {
          const d1 = d[2];
          if (d1 > "3") d = d.slice(0, 2) + "0" + d1;
        }
      } else {
        // DD/MM/YYYY (standard)
        // Day start (index 0)
        if (d.length === 1) {
          const d1 = d[0];
          if (d1 > "3") d = "0" + d1;
        }
        // Month start (index 2)
        if (d.length === 3) {
          const m1 = d[2];
          if (m1 > "1") d = d.slice(0, 2) + "0" + m1;
        }
      }

      // Auto-fill current year if they finished entering Day and Month (4 digits) and are adding characters
      if (d.length === 4 && isAdding) {
        const currentYear = new Date().getFullYear();
        d = d + String(currentYear);
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

  const placeholderText = placeholder || (format === "MM/DD/YYYY" ? "mm/dd/yyyy" : "dd/mm/yyyy");

  // ===== Years list bounded by minYear and maxYear =====
  const years = useMemo(() => {
    const start = Math.min(Number(minYear) || 1940, Number(maxYear) || 2050);
    const end = Math.max(Number(minYear) || 1940, Number(maxYear) || 2050);
    const arr = [];
    for (let y = start; y <= end; y++) arr.push(y);
    return arr;
  }, [minYear, maxYear]);

  useEffect(() => {
    if (!open) return;
    if (mode !== "year") return;

    const current = view.getFullYear();
    const idx = years.indexOf(current);
    if (idx < 0) return;

    requestAnimationFrame(() => {
      const el = yearListRef.current;
      if (!el) return;
      const row = Math.floor(idx / 4);
      // Row height is ~36px (h-9) + 8px (gap-2) = 44px
      const targetScroll = Math.max(0, row * 44 - 88);
      el.scrollTop = targetScroll;
    });
  }, [mode, open, view, years]);

  const selectYear = (y) => {
    setView((v) => new Date(y, v.getMonth(), 1));
    setMode("day");
  };

  return (
    <div className="flex flex-col w-full text-start">
      {label &&
        (compact ? (
          <span className="mb-1.5 block text-xs text-app-muted-light text-right w-full">
            {label}
            {required ? <span className="text-app-red">*</span> : null}
          </span>
        ) : (
          <span className="mb-3 flex items-center gap-2 text-base font-medium text-app-text">
            <TagIcon className="size-4 shrink-0 text-app-yellow" />
            <span>{label}</span>
            {required ? <span className="text-app-red">*</span> : null}
          </span>
        ))}

      {/* Input */}
      <div
        ref={inputWrapRef}
        className={[
          "relative flex w-full items-center justify-between rounded-lg px-4 text-sm transition outline-none",
          compact ? "h-9 bg-black/35 border" : "h-[46px] bg-app-panel-soft/40 border",
          error
            ? "border-app-red focus-within:border-app-red focus-within:ring-1 focus-within:ring-app-red"
            : "border-app-muted/50 focus-within:border-app-yellow focus-within:ring-1 focus-within:ring-app-yellow",
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
          title="فتح التقويم"
        >
          <CalendarIcon size={18} />
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
          placeholder={placeholderText}
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
              <div className="bg-app-card border border-app-line rounded-2xl shadow-xl p-3.5 text-app-text">
                {/* Header */}
                <div className="flex items-center justify-between px-1 pb-2.5 border-b border-app-line" dir="rtl">
                  <div className="flex items-center gap-1.5">
                    <button
                      type="button"
                      onClick={() => setMode((m) => (m === "month" ? "day" : "month"))}
                      className={`text-sm font-semibold transition px-2.5 py-1 rounded-lg flex items-center gap-1 ${
                        mode === "month"
                          ? "bg-app-yellow text-app-bg font-bold shadow-sm"
                          : "text-app-text hover:text-app-yellow hover:bg-app-card-soft"
                      }`}
                      title="اختر الشهر"
                    >
                      <span>{MONTHS[view.getMonth()]}</span>
                      <ChevronDown
                        size={14}
                        className={`transition-transform duration-200 ${mode === "month" ? "rotate-180" : ""}`}
                      />
                    </button>
                    <button
                      type="button"
                      onClick={() => setMode((m) => (m === "year" ? "day" : "year"))}
                      className={`text-sm font-semibold transition px-2.5 py-1 rounded-lg flex items-center gap-1 ${
                        mode === "year"
                          ? "bg-app-yellow text-app-bg font-bold shadow-sm"
                          : "text-app-text hover:text-app-yellow hover:bg-app-card-soft"
                      }`}
                      title="اختر السنة"
                    >
                      <span>{view.getFullYear()}</span>
                      <ChevronDown
                        size={14}
                        className={`transition-transform duration-200 ${mode === "year" ? "rotate-180" : ""}`}
                      />
                    </button>
                  </div>

                  <div className="flex items-center gap-1">
                    <button
                      type="button"
                      onClick={() => {
                        if (mode === "year") {
                          setView((v) => new Date(Math.max(years[0] || 1940, v.getFullYear() - 10), v.getMonth(), 1));
                        } else if (mode === "month") {
                          setView((v) => new Date(v.getFullYear() - 1, v.getMonth(), 1));
                        } else {
                          goPrevMonth();
                        }
                      }}
                      className="p-1.5 rounded-lg text-app-muted-light hover:text-app-text hover:bg-app-card-soft transition"
                      title="السابق"
                    >
                      <ChevronRight size={18} />
                    </button>
                    <button
                      type="button"
                      onClick={() => {
                        if (mode === "year") {
                          setView((v) => new Date(Math.min(years[years.length - 1] || 2050, v.getFullYear() + 10), v.getMonth(), 1));
                        } else if (mode === "month") {
                          setView((v) => new Date(v.getFullYear() + 1, v.getMonth(), 1));
                        } else {
                          goNextMonth();
                        }
                      }}
                      className="p-1.5 rounded-lg text-app-muted-light hover:text-app-text hover:bg-app-card-soft transition"
                      title="التالي"
                    >
                      <ChevronLeft size={18} />
                    </button>
                  </div>
                </div>

                {/* BODY */}
                {mode === "year" ? (
                  <div
                    ref={yearListRef}
                    className="max-h-[220px] overflow-y-auto py-2 pr-1 scrollbar-hidden"
                  >
                    <div className="grid grid-cols-4 gap-2" dir="ltr">
                      {years.map((y) => {
                        const isCurrent = y === view.getFullYear();
                        return (
                          <button
                            key={y}
                            type="button"
                            onClick={() => selectYear(y)}
                            className={[
                              "h-9 rounded-lg text-xs font-semibold transition-all flex items-center justify-center text-center",
                              isCurrent
                                ? "bg-app-yellow text-app-bg font-bold shadow-md ring-2 ring-app-yellow/30"
                                : "text-app-text hover:text-app-yellow hover:bg-app-card-soft border border-app-line hover:border-app-yellow/40",
                            ].join(" ")}
                          >
                            {y}
                          </button>
                        );
                      })}
                    </div>
                  </div>
                ) : mode === "month" ? (
                  <div className="grid grid-cols-3 gap-2 py-2" dir="rtl">
                    {MONTHS.map((m, idx) => {
                      const isCurrent = idx === view.getMonth();
                      return (
                        <button
                          key={m}
                          type="button"
                          onClick={() => {
                            setView((v) => new Date(v.getFullYear(), idx, 1));
                            setMode("day");
                          }}
                          className={[
                            "h-11 rounded-xl text-xs font-semibold transition-all flex items-center justify-center text-center px-1.5 leading-snug",
                            isCurrent
                              ? "bg-app-yellow text-app-bg font-bold shadow-md ring-2 ring-app-yellow/30"
                              : "text-app-text hover:text-app-yellow hover:bg-app-card-soft border border-app-line hover:border-app-yellow/40",
                          ].join(" ")}
                        >
                          {m}
                        </button>
                      );
                    })}
                  </div>
                ) : (
                  <>
                    {/* Weekdays */}
                    <div className="grid grid-cols-7 gap-1 mb-1 pt-2" dir="rtl">
                      {WEEKDAYS.map((d) => (
                        <div
                          key={d}
                          className="text-[11px] text-app-muted-light font-medium text-center py-1 select-none"
                        >
                          {d.slice(0, 3)}
                        </div>
                      ))}
                    </div>

                    {/* Days */}
                    <div className="space-y-1" dir="rtl">
                      {weeks.map((week, wIdx) => {
                        return (
                          <div key={wIdx} className="grid grid-cols-7 gap-1">
                            {week.map((cell, i) => {
                              const inMonth = !!cell?.inMonth && !!cell?.date;
                              if (!inMonth) {
                                return <div key={`b-${wIdx}-${i}`} className="h-8" />;
                              }

                              const iso = toISO(cell.date);
                              const isActive = iso === activeISO;
                              const isToday = iso === todayISO;

                              return (
                                <div
                                  key={`${iso}-${i}`}
                                  className="h-8 flex items-center justify-center p-0 m-0"
                                >
                                  <button
                                    type="button"
                                    onClick={() => pickDate(cell.date)}
                                    className={[
                                      "w-8 h-8 rounded-full flex items-center justify-center text-xs font-medium transition-all",
                                      isActive
                                        ? "bg-app-yellow text-app-bg font-bold shadow-md ring-2 ring-app-yellow/30"
                                        : isToday
                                          ? "border border-app-yellow text-app-yellow hover:bg-app-card-soft font-semibold"
                                          : "text-app-text hover:bg-app-card-soft hover:text-app-yellow",
                                    ].join(" ")}
                                    title={iso}
                                  >
                                    {cell.date.getDate()}
                                  </button>
                                </div>
                              );
                            })}
                          </div>
                        );
                      })}
                    </div>

                    {/* Footer */}
                    <div className="mt-3 pt-2.5 border-t border-app-line flex items-center justify-between" dir="rtl">
                      <button
                        type="button"
                        onClick={() => {
                          const today = new Date();
                          setView(today);
                          pickDate(today);
                        }}
                        className="text-xs text-app-yellow hover:underline font-medium px-1 py-0.5"
                      >
                        اليوم
                      </button>
                      {allowClear && !!value && (
                        <button
                          type="button"
                          onClick={clear}
                          className="text-xs text-app-muted-light hover:text-app-red transition px-1 py-0.5"
                        >
                          مسح
                        </button>
                      )}
                    </div>
                  </>
                )}
              </div>
            </div>
          </>,
          document.body,
        )}
      {error && (
        <span className="mt-1.5 block text-xs text-app-red text-right w-full" role="alert">
          {error}
        </span>
      )}
    </div>
  );
}
