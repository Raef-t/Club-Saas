"use client";

import { useEffect, useMemo, useRef, useState } from "react";
import { createPortal } from "react-dom";
import { 
  CalendarIcon,
  ChevronLeft,
  ChevronRight,
  ChevronDownIcon as ChevronDown,
  XIcon as X,
  TagIcon
} from "@/components/icons/Icons";

function pad2(n) {
  return String(n).padStart(2, "0");
}

function toISO(date) {
  if (!date) return "";
  const y = date.getFullYear();
  const m = pad2(date.getMonth() + 1);
  const d = pad2(date.getDate());
  return `${y}-${m}-${d}`;
}

function fromISO(iso) {
  if (!iso) return null;
  const [y, m, d] = String(iso)
    .split("-")
    .map((x) => Number(x));
  if (!y || !m || !d) return null;
  const dt = new Date(y, m - 1, d);
  if (dt.getFullYear() !== y || dt.getMonth() !== m - 1 || dt.getDate() !== d)
    return null;
  return dt;
}

function formatDisplay(iso, format = "DD/MM/YYYY") {
  const dt = fromISO(iso);
  if (!dt) return "";
  const dd = pad2(dt.getDate());
  const mm = pad2(dt.getMonth() + 1);
  const yyyy = dt.getFullYear();
  if (format === "YYYY/MM/DD") return `${yyyy}/${mm}/${dd}`;
  return format === "MM/DD/YYYY"
    ? `${mm}/${dd}/${yyyy}`
    : `${dd}/${mm}/${yyyy}`;
}

function cleanTyped(raw) {
  return String(raw || "")
    .replace(/\D/g, "")
    .slice(0, 8);
}

function applyMask(digits, format = "DD/MM/YYYY") {
  if (format === "YYYY/MM/DD") {
    const y = digits.slice(0, 4);
    const m = digits.slice(4, 6);
    const d = digits.slice(6, 8);
    if (digits.length <= 4) return y;
    if (digits.length <= 6) return `${y}/${m}`;
    return `${y}/${m}/${d}`;
  }

  const a = digits.slice(0, 2);
  const b = digits.slice(2, 4);
  const c = digits.slice(4, 8);

  if (digits.length <= 2) return a;
  if (digits.length <= 4) return `${a}/${b}`;
  return `${a}/${b}/${c}`;
}

function parseTyped(masked, format = "DD/MM/YYYY") {
  let m, yyyy, mm, dd;

  if (format === "YYYY/MM/DD") {
    m = String(masked || "")
      .trim()
      .match(/^(\d{4})\/(\d{2})\/(\d{2})$/);
    if (!m) return null;
    yyyy = Number(m[1]);
    mm = Number(m[2]);
    dd = Number(m[3]);
  } else {
    m = String(masked || "")
      .trim()
      .match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
    if (!m) return null;

    let p1 = Number(m[1]);
    let p2 = Number(m[2]);
    yyyy = Number(m[3]);

    if (format === "MM/DD/YYYY") {
      mm = p1;
      dd = p2;
    } else {
      dd = p1;
      mm = p2;
    }
  }

  if (yyyy < 1900 || yyyy > 2100) return null;
  if (mm < 1 || mm > 12) return null;
  if (dd < 1 || dd > 31) return null;

  const dt = new Date(yyyy, mm - 1, dd);
  if (
    dt.getFullYear() !== yyyy ||
    dt.getMonth() !== mm - 1 ||
    dt.getDate() !== dd
  )
    return null;

  return toISO(dt);
}

const MONTHS = [
  "يناير",
  "فبراير",
  "مارس",
  "أبريل",
  "مايو",
  "يونيو",
  "يوليو",
  "أغسطس",
  "سبتمبر",
  "أكتوبر",
  "نوفمبر",
  "ديسمبر",
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
  autoDefault = true,
}) {
  const inputWrapRef = useRef(null);
  const dropdownRef = useRef(null);
  const yearListRef = useRef(null);

  const [mounted, setMounted] = useState(false);
  const [open, setOpen] = useState(false);

  // day | year
  const [mode, setMode] = useState("day");

  const [pos, setPos] = useState({
    top: 0,
    left: 0,
    width: 280,
    placement: "bottom",
  });

  const [digits, setDigits] = useState("");

  const inputText = useMemo(
    () => applyMask(digits, format),
    [digits, format],
  );

  const selectedDate = useMemo(() => fromISO(value), [value]);
  const [view, setView] = useState(() => selectedDate || new Date());

  useEffect(() => {
    setMounted(true);
    if (autoDefault && !value && onChange) {
      onChange(toISO(new Date()));
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
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

    const desiredW = 280;
    const width = Math.min(desiredW, window.innerWidth - margin * 2);

    const estimatedH = dropdownRef.current?.offsetHeight || 315;

    const spaceBelow = window.innerHeight - rect.bottom;
    const spaceAbove = rect.top;
    const shouldFlip = spaceBelow < estimatedH && spaceAbove > spaceBelow;

    let top = shouldFlip ? rect.top - estimatedH - 8 : rect.bottom + 8;
    let left = rect.right - width; // align right for RTL

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
    for (let i = 0; i < firstDay; i++)
      cells.push({ date: null, inMonth: false });

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

  const goPrevMonth = () =>
    setView((v) => new Date(v.getFullYear(), v.getMonth() - 1, 1));
  const goNextMonth = () =>
    setView((v) => new Date(v.getFullYear(), v.getMonth() + 1, 1));

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

  const placeholderText =
    placeholder ||
    (format === "MM/DD/YYYY"
      ? "mm/dd/yyyy"
      : "dd/mm/yyyy");

  // ===== Years list =====
  const years = useMemo(() => {
    const center = view.getFullYear();
    const start = center - 60;
    const end = center + 60;
    const arr = [];
    for (let y = start; y <= end; y++) arr.push(y);
    return arr;
  }, [view]);

  useEffect(() => {
    if (!open) return;
    if (mode !== "year") return;

    const current = view.getFullYear();
    const idx = years.indexOf(current);
    if (idx < 0) return;

    requestAnimationFrame(() => {
      const el = yearListRef.current;
      if (!el) return;
      el.scrollTop = Math.max(0, idx * 34 - 120);
    });
  }, [mode, open, view, years]);

  const selectYear = (y) => {
    setView((v) => new Date(y, v.getMonth(), 1));
    setMode("day");
  };

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
              <div className="bg-app-card border border-app-line rounded-2xl shadow-xl p-4 text-app-text">
                {/* Header */}
                <div className="flex items-center justify-between" dir="rtl">
                  <div className="text-sm font-semibold text-white">
                    التقويم
                  </div>

                  <button
                    type="button"
                    onClick={() =>
                      setMode((m) => (m === "day" ? "year" : "day"))
                    }
                    className="p-1 rounded-full hover:bg-app-card-soft transition"
                    title="السنوات"
                  >
                    <ChevronDown size={18} className="text-app-muted" />
                  </button>
                </div>

                {/* Month row */}
                <div
                  className="mt-3 flex items-center justify-between"
                  dir="rtl"
                >
                  <div className="text-xs text-app-muted-light">
                    {MONTHS[view.getMonth()]} {view.getFullYear()}
                  </div>

                  <div className="flex items-center gap-2">
                    <button
                      type="button"
                      onClick={goPrevMonth}
                      className="p-1 rounded-full hover:bg-app-card-soft transition"
                      title="السابق"
                    >
                      <ChevronRight size={18} className="text-app-muted-light" />
                    </button>
                    <button
                      type="button"
                      onClick={goNextMonth}
                      className="p-1 rounded-full hover:bg-app-card-soft transition"
                      title="التالي"
                    >
                      <ChevronLeft size={18} className="text-app-muted-light" />
                    </button>
                  </div>
                </div>

                <div className="mt-3 mb-3 h-px w-full bg-app-line/50" />

                {/* BODY */}
                {mode === "year" ? (
                  <div
                    ref={yearListRef}
                    className="max-h-[220px] overflow-auto pr-1 scrollbar-hidden"
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
                              "h-8 rounded-lg text-xs transition",
                              isCurrent
                                ? "bg-app-yellow text-app-bg font-bold"
                                : "text-app-text hover:bg-app-card-soft border border-app-line",
                            ].join(" ")}
                          >
                            {y}
                          </button>
                        );
                      })}
                    </div>
                  </div>
                ) : (
                  <>
                    {/* Weekdays */}
                    <div className="grid grid-cols-7 gap-0 mb-1" dir="rtl">
                      {WEEKDAYS.map((d) => (
                        <div
                          key={d}
                          className="text-[10px] text-app-muted font-medium text-center py-1"
                        >
                          {d}
                        </div>
                      ))}
                    </div>

                    {/* Days */}
                    <div className="space-y-1" dir="rtl">
                      {weeks.map((week, wIdx) => {
                        return (
                          <div key={wIdx} className="grid grid-cols-7 gap-0">
                            {week.map((cell, i) => {
                              const inMonth = !!cell?.inMonth && !!cell?.date;
                              if (!inMonth) {
                                return (
                                  <div key={`b-${wIdx}-${i}`} className="h-8" />
                                );
                              }

                              const iso = toISO(cell.date);
                              const isActive = iso === activeISO;

                              return (
                                <div
                                  key={`${iso}-${i}`}
                                  className="h-8 flex items-center justify-center p-0 m-0"
                                >
                                  <button
                                    type="button"
                                    onClick={() => pickDate(cell.date)}
                                    className={[
                                      "w-7 h-7 rounded-full flex items-center justify-center text-xs transition",
                                      isActive
                                        ? "bg-app-yellow text-app-bg font-bold shadow-sm"
                                        : "text-app-text hover:bg-app-card-soft",
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
                  </>
                )}
              </div>
            </div>
          </>,
          document.body,
        )}
    </div>
  );
}
