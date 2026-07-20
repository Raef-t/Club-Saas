"use client";

import { useState, useEffect, useMemo, useCallback, useRef } from "react";
import Image from "next/image";
import PageHeader from "@/components/common/PageHeader";
import Button from "@/components/ui/Button";
import SectionCard from "@/components/ui/SectionCard";
import Dropdown from "@/components/ui/Dropdown";
import { ClockIcon, SettingsIcon } from "@/components/icons/Icons";
import { useGetScheduleQuery } from "@/lib/api/scheduleApi";
import "./schedule.css";

// ─── Constants ───
const DAYS = [
  { key: "sun", label: "الأحد" },
  { key: "mon", label: "الاثنين" },
  { key: "tue", label: "الثلاثاء" },
  { key: "wed", label: "الأربعاء" },
  { key: "thu", label: "الخميس" },
  { key: "sat", label: "السبت" },
];

const DURATION_OPTIONS = [
  { value: 30, label: "30 دقيقة" },
  { value: 45, label: "45 دقيقة" },
  { value: 60, label: "ساعة واحدة" },
  { value: 90, label: "ساعة ونصف" },
  { value: 120, label: "ساعتين" },
];

const STORAGE_KEY = "technogym_schedule_data";

// ─── Helpers ───
function pad2(n) {
  return String(n).padStart(2, "0");
}

/** Generate time slot labels from start to end with given step (minutes) */
function generateTimeSlots(startTime, endTime, stepMinutes) {
  const slots = [];
  if (!startTime || !endTime || !stepMinutes) return slots;

  const [sh, sm] = startTime.split(":").map(Number);
  const [eh, em] = endTime.split(":").map(Number);

  let startTotalMin = sh * 60 + sm;
  const endTotalMin = eh * 60 + em;

  // Handle overnight (e.g. 22:00 → 02:00)
  const adjustedEnd =
    endTotalMin <= startTotalMin ? endTotalMin + 24 * 60 : endTotalMin;

  while (startTotalMin < adjustedEnd) {
    const nextMin = startTotalMin + stepMinutes;
    const fromH = Math.floor(startTotalMin / 60) % 24;
    const fromM = startTotalMin % 60;
    const toH = Math.floor(nextMin / 60) % 24;
    const toM = nextMin % 60;

    slots.push({
      key: `${pad2(fromH)}${pad2(fromM)}`,
      from: `${pad2(fromH)}:${pad2(fromM)}`,
      to: `${pad2(toH)}:${pad2(toM)}`,
      label: `${pad2(fromH)}:${pad2(fromM)}`,
    });

    startTotalMin = nextMin;
  }

  return slots;
}

/** Build a default empty schedule data object */
function buildEmptyData(morningSlots, eveningSlots) {
  const data = {};
  DAYS.forEach((day) => {
    data[day.key] = {};
    [...morningSlots, ...eveningSlots].forEach((slot) => {
      data[day.key][slot.key] = "";
    });
  });
  return data;
}

// ─── Print Utility ───
function PrintIcon({ className = "size-5" }) {
  return (
    <svg
      className={className}
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="1.8"
      strokeLinecap="round"
      strokeLinejoin="round"
      aria-hidden="true"
    >
      <polyline points="6 9 6 2 18 2 18 9" />
      <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" />
      <rect x="6" y="14" width="12" height="8" />
    </svg>
  );
}

function ScheduleIcon({ className = "size-5" }) {
  return (
    <svg
      className={className}
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="1.8"
      strokeLinecap="round"
      strokeLinejoin="round"
      aria-hidden="true"
    >
      <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
      <line x1="3" y1="9" x2="21" y2="9" />
      <line x1="3" y1="15" x2="21" y2="15" />
      <line x1="9" y1="3" x2="9" y2="21" />
      <line x1="15" y1="3" x2="15" y2="21" />
    </svg>
  );
}

function ChevronDown({ className = "size-4" }) {
  return (
    <svg
      className={className}
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
      aria-hidden="true"
    >
      <path d="m6 9 6 6 6-6" />
    </svg>
  );
}

// ─── Editable Cell ───
function EditableCell({ value, onChange, placeholder = "+" }) {
  const [editing, setEditing] = useState(false);
  const [tempValue, setTempValue] = useState(value);
  const inputRef = useRef(null);

  useEffect(() => {
    setTempValue(value);
  }, [value]);

  useEffect(() => {
    if (editing && inputRef.current) {
      inputRef.current.focus();
      inputRef.current.select();
    }
  }, [editing]);

  const handleConfirm = () => {
    setEditing(false);
    if (tempValue !== value) {
      onChange(tempValue);
    }
  };

  if (editing) {
    return (
      <textarea
        ref={inputRef}
        className="schedule-cell-input"
        value={tempValue}
        onChange={(e) => setTempValue(e.target.value)}
        onBlur={handleConfirm}
        onKeyDown={(e) => {
          if (e.key === "Enter" && !e.shiftKey) {
            e.preventDefault();
            handleConfirm();
          }
          if (e.key === "Escape") {
            setTempValue(value);
            setEditing(false);
          }
        }}
        placeholder="اسم المدرب..."
        rows={1}
      />
    );
  }

  return (
    <div
      className="schedule-cell-content"
      onClick={() => setEditing(true)}
      title="انقر للتعديل"
    >
      {value ? (
        value
      ) : (
        <span className="schedule-cell-empty">{placeholder}</span>
      )}
    </div>
  );
}

// ─── Schedule Table ───
function ScheduleTable({
  title,
  slots,
  scheduleData,
  onCellChange,
  periodKey,
}) {
  if (slots.length === 0) return null;

  return (
    <div className="schedule-wrapper schedule-table-enter">
      <table className="schedule-table">
        <caption>{title}</caption>
        <thead>
          <tr>
            <th className="day-cell">اليوم</th>
            {slots.map((slot) => (
              <th key={slot.key}>
                <div className="flex flex-col items-center gap-0.5 leading-tight">
                  <span>{slot.from}</span>
                  <span className="text-[10px] text-app-muted-light">↔</span>
                  <span>{slot.to}</span>
                </div>
              </th>
            ))}
          </tr>
        </thead>
        <tbody>
          {DAYS.map((day) => (
            <tr key={day.key}>
              <td className="day-cell">{day.label}</td>
              {slots.map((slot) => (
                <td
                  key={`${day.key}-${slot.key}`}
                  className="schedule-cell"
                  data-label={`${slot.from} - ${slot.to}`}
                >
                  <EditableCell
                    value={
                      scheduleData?.[day.key]?.[`${periodKey}_${slot.key}`] ||
                      ""
                    }
                    onChange={(val) =>
                      onCellChange(day.key, `${periodKey}_${slot.key}`, val)
                    }
                  />
                </td>
              ))}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

// ─── Main Component ───
export default function ScheduleClient() {
  // Period settings with defaults matching the original image
  const [morningStart, setMorningStart] = useState("10:00");
  const [morningEnd, setMorningEnd] = useState("14:00");
  const [eveningStart, setEveningStart] = useState("15:00");
  const [eveningEnd, setEveningEnd] = useState("00:00");
  const [slotDuration, setSlotDuration] = useState(60);

  // Schedule data
  const [scheduleData, setScheduleData] = useState({});

  // API Data
  const { data: apiScheduleResponse, isLoading } = useGetScheduleQuery();

  // Populate scheduleData from API
  useEffect(() => {
    if (apiScheduleResponse?.data) {
      const apiData = apiScheduleResponse.data;
      const apiToLocalDays = {
        Sunday: "sun",
        Monday: "mon",
        Tuesday: "tue",
        Wednesday: "wed",
        Thursday: "thu",
        Friday: "fri",
        Saturday: "sat",
      };

      setScheduleData((prev) => {
        const newData = { ...prev };
        
        Object.entries(apiData).forEach(([apiDay, sessions]) => {
          const localDay = apiToLocalDays[apiDay];
          if (!localDay) return;
          
          if (!newData[localDay]) newData[localDay] = {};
          
          sessions.forEach(session => {
            if (!session.start_time) return;
            const [h, m] = session.start_time.split(":");
            const slotKey = `${pad2(h)}${pad2(m)}`;
            
            const cellText = [session.plan_name, session.coach?.name].filter(Boolean).join(" - ");
            
            newData[localDay][`morning_${slotKey}`] = cellText;
            newData[localDay][`evening_${slotKey}`] = cellText;
          });
        });

        return newData;
      });
    }
  }, [apiScheduleResponse]);

  // UI state
  const [showSettings, setShowSettings] = useState(false);
  const [loaded, setLoaded] = useState(false);

  // Generate time slots
  const morningSlots = useMemo(
    () => generateTimeSlots(morningStart, morningEnd, slotDuration),
    [morningStart, morningEnd, slotDuration],
  );

  const eveningSlots = useMemo(
    () => generateTimeSlots(eveningStart, eveningEnd, slotDuration),
    [eveningStart, eveningEnd, slotDuration],
  );

  // Load from localStorage on mount
  useEffect(() => {
    try {
      const stored = localStorage.getItem(STORAGE_KEY);
      if (stored) {
        const parsed = JSON.parse(stored);
        if (parsed.morningStart) setMorningStart(parsed.morningStart);
        if (parsed.morningEnd) setMorningEnd(parsed.morningEnd);
        if (parsed.eveningStart) setEveningStart(parsed.eveningStart);
        if (parsed.eveningEnd) setEveningEnd(parsed.eveningEnd);
        if (parsed.slotDuration) setSlotDuration(parsed.slotDuration);
        if (parsed.scheduleData) setScheduleData(parsed.scheduleData);
      }
    } catch {
      // ignore parse errors
    }
    setLoaded(true);
  }, []);

  // Save to localStorage on change (debounced)
  useEffect(() => {
    if (!loaded) return;
    const timer = setTimeout(() => {
      try {
        localStorage.setItem(
          STORAGE_KEY,
          JSON.stringify({
            morningStart,
            morningEnd,
            eveningStart,
            eveningEnd,
            slotDuration,
            scheduleData,
          }),
        );
      } catch {
        // ignore storage errors
      }
    }, 500);
    return () => clearTimeout(timer);
  }, [
    morningStart,
    morningEnd,
    eveningStart,
    eveningEnd,
    slotDuration,
    scheduleData,
    loaded,
  ]);

  // Cell change handler
  const handleCellChange = useCallback((dayKey, slotKey, value) => {
    setScheduleData((prev) => ({
      ...prev,
      [dayKey]: {
        ...(prev[dayKey] || {}),
        [slotKey]: value,
      },
    }));
  }, []);

  // Clear all schedule data
  const handleClearAll = () => {
    if (window.confirm("هل أنت متأكد من حذف جميع البيانات؟ لا يمكن التراجع.")) {
      setScheduleData({});
    }
  };

  // ─── Print ───
  const handlePrint = () => {
    const buildTableHtml = (title, slots, periodKey) => {
      if (slots.length === 0) return "";

      const headerCells = slots
        .map(
          (s) =>
            `<th><div style="line-height:1.4"><div>${s.from}</div><div style="font-size:10px;color:#999">↓</div><div>${s.to}</div></div></th>`,
        )
        .join("");

      const bodyRows = DAYS.map(
        (day, i) => `
        <tr class="${i % 2 === 1 ? "even-row" : ""}">
          <td class="day-cell">${day.label}</td>
          ${slots
            .map((slot) => {
              const val =
                scheduleData?.[day.key]?.[`${periodKey}_${slot.key}`] || "";
              return `<td>${val}</td>`;
            })
            .join("")}
        </tr>`,
      ).join("");

      return `
        <div class="period-section">
          <h2 style="text-align:center;margin:30px 0 10px;font-size:18px;color:#111;font-weight:700;">${title}</h2>
          <table>
            <thead><tr><th class="day-cell">اليوم</th>${headerCells}</tr></thead>
            <tbody>${bodyRows}</tbody>
          </table>
        </div>`;
    };

    const morningHtml = buildTableHtml(
      "الفترة الصباحية",
      morningSlots,
      "morning",
    );
    const eveningHtml = buildTableHtml(
      "الفترة المسائية",
      eveningSlots,
      "evening",
    );

    const printWindow = window.open("", "_blank");
    printWindow.document.write(`
      <html>
      <head>
        <title>جدول الدوام - TechnoGYM</title>
        <style>
          @import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap');
          * { box-sizing: border-box; margin: 0; padding: 0; }
          body {
            font-family: 'Tajawal', sans-serif;
            direction: rtl;
            text-align: right;
            padding: 30px 25px;
            color: #111;
            background: #fff;
          }
          .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid #d97706;
            padding-bottom: 18px;
            margin-bottom: 10px;
          }
          .header-right {
            display: flex;
            align-items: center;
            gap: 16px;
          }
          .header-right img {
            width: 80px;
            height: auto;
            border-radius: 8px;
          }
          .logo-title {
            font-size: 24px;
            font-weight: bold;
            color: #111;
            letter-spacing: 2px;
          }
          .logo-title span {
            color: #d97706;
          }
          .logo-subtitle {
            font-size: 11px;
            color: #666;
            margin-top: 4px;
            font-weight: 500;
          }
          .meta-area {
            text-align: left;
            font-size: 12px;
            color: #555;
          }
          .meta-area p {
            margin: 3px 0;
          }
          .main-title {
            text-align: center;
            font-size: 22px;
            font-weight: 700;
            color: #111;
            margin: 20px 0 8px;
          }
          table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
          }
          th, td {
            border: 1px solid #d1d5db;
            padding: 8px 4px;
            text-align: center;
            font-size: 11px;
          }
          th {
            background: #f3f4f6;
            font-weight: 700;
            color: #374151;
            font-size: 10px;
          }
          td {
            color: #1f2937;
            font-weight: 500;
          }
          .day-cell {
            background: #f3f4f6;
            font-weight: 700;
            font-size: 13px;
            min-width: 70px;
          }
          .even-row td {
            background: #fef9e7;
          }
          .even-row .day-cell {
            background: #fdf3d3;
          }
          .footer {
            margin-top: 40px;
            border-top: 1px solid #e2e8f0;
            padding-top: 14px;
            text-align: center;
            font-size: 10px;
            color: #94a3b8;
            font-weight: 500;
          }
          .period-section {
            page-break-after: always;
            page-break-inside: avoid;
          }
          .period-section:last-of-type {
            page-break-after: auto;
          }
          @media print {
            body { padding: 15px; }
            @page { 
              size: landscape; 
              margin: 10mm;
            }
            .period-section {
              page-break-after: always;
              page-break-inside: avoid;
            }
            .period-section:last-of-type {
              page-break-after: auto;
            }
          }
        </style>
      </head>
      <body>
        <div class="header">
          <div class="header-right">
            <img src="/img/Logo11.jpeg" alt="Logo" />
            <div>
              <div class="logo-title">TECHNO<span>GYM</span></div>
              <div class="logo-subtitle">نادي تكنولوجي جيم الرياضي</div>
            </div>
          </div>
          <div class="meta-area">
            <p>تاريخ الطباعة: ${new Date().toLocaleDateString("ar-SY")}</p>
            <p>نظام إدارة الصالات الرياضية</p>
          </div>
        </div>

        <h1 class="main-title">جدول الدوام الأسبوعي</h1>

        ${morningHtml}
        ${eveningHtml}

        <div class="footer">
          نظام تكنولوجي جيم المتكامل لإدارة الأندية الرياضية &copy; جميع الحقوق محفوظة
        </div>

        <script>
          window.onload = function() {
            setTimeout(function() {
              window.print();
              setTimeout(function() {
                window.close();
              }, 500);
            }, 300);
          };
        </script>
      </body>
      </html>
    `);
    printWindow.document.close();
  };

  if (!loaded || isLoading) {
    return (
      <div className="flex items-center justify-center min-h-[400px]">
        <div className="text-app-muted text-sm">جاري التحميل...</div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Page Header */}
      <PageHeader
        eyebrow="إدارة الجدول"
        title="جدول الدوام الأسبوعي"
        subtitle="إدارة وتنظيم جدول دوام المدربين مع إمكانية تخصيص الأوقات والطباعة"
        action={
          <div className="flex flex-wrap items-center gap-2 sm:gap-3">
            <Button
              tone="outline"
              onClick={() => setShowSettings((v) => !v)}
              icon={<SettingsIcon className="size-4" />}
            >
              {showSettings ? "إخفاء الإعدادات" : "إعدادات الأوقات"}
            </Button>
            <Button
              tone="outline"
              onClick={handleClearAll}
              className="text-app-red!"
            >
              مسح الكل
            </Button>
            <Button
              tone="primary"
              onClick={handlePrint}
              icon={<PrintIcon className="size-4" />}
            >
              طباعة الجدول
            </Button>
          </div>
        }
      />

      {/* Time Settings Panel */}
      {showSettings && (
        <SectionCard
          title="إعدادات الأوقات"
          subtitle="قم بتخصيص أوقات الفترات ومدة كل حصة — الجدول يتحدث تلقائياً"
          className="schedule-table-enter"
        >
          <div className="px-5 pb-5">
            <div className="schedule-settings">
              {/* Morning Period */}
              <div className="schedule-period-config">
                <h3>
                  <span className="period-dot" />
                  الفترة الصباحية
                </h3>
                <div className="schedule-time-row">
                  <div className="schedule-time-field">
                    <label>وقت البداية</label>
                    <input
                      type="time"
                      value={morningStart}
                      onChange={(e) => setMorningStart(e.target.value)}
                    />
                  </div>
                  <div className="schedule-time-field">
                    <label>وقت النهاية</label>
                    <input
                      type="time"
                      value={morningEnd}
                      onChange={(e) => setMorningEnd(e.target.value)}
                    />
                  </div>
                </div>
              </div>

              {/* Evening Period */}
              <div className="schedule-period-config">
                <h3>
                  <span className="period-dot" />
                  الفترة المسائية
                </h3>
                <div className="schedule-time-row">
                  <div className="schedule-time-field">
                    <label>وقت البداية</label>
                    <input
                      type="time"
                      value={eveningStart}
                      onChange={(e) => setEveningStart(e.target.value)}
                    />
                  </div>
                  <div className="schedule-time-field">
                    <label>وقت النهاية</label>
                    <input
                      type="time"
                      value={eveningEnd}
                      onChange={(e) => setEveningEnd(e.target.value)}
                    />
                  </div>
                </div>
              </div>
            </div>

            {/* Duration */}
            <div className="mt-4 flex items-center gap-4 rounded-xl bg-app-card-soft border border-app-line px-4 py-3">
              <div className="flex items-center gap-2 text-sm text-app-text">
                <ClockIcon className="size-4 text-app-yellow" />
                <span className="font-medium">مدة الحصة الواحدة:</span>
              </div>
              <Dropdown
                options={DURATION_OPTIONS.map((opt) => ({
                  value: opt.value,
                  label: opt.label,
                }))}
                value={slotDuration}
                onChange={(val) => setSlotDuration(Number(val))}
                placeholder="اختر المدة"
                icon={ClockIcon}
                className="w-48"
              />
              <span className="text-xs text-app-muted">
                (صباحي: {morningSlots.length} حصة | مسائي: {eveningSlots.length}{" "}
                حصة)
              </span>
            </div>
          </div>
        </SectionCard>
      )}

      {/* Morning Schedule */}
      <SectionCard
        title="الفترة الصباحية"
        subtitle={`من ${morningStart} إلى ${morningEnd} — ${morningSlots.length} حصة`}
        action={
          <div className="flex items-center gap-1 text-xs">
            <ClockIcon className="size-3.5" />
            <span>
              {morningStart} - {morningEnd}
            </span>
          </div>
        }
        contentClassName="px-5 pb-5"
      >
        {morningSlots.length > 0 ? (
          <ScheduleTable
            title="الفترة الصباحية"
            slots={morningSlots}
            scheduleData={scheduleData}
            onCellChange={handleCellChange}
            periodKey="morning"
          />
        ) : (
          <div className="py-12 text-center text-sm text-app-muted">
            لا توجد حصص — يرجى ضبط أوقات الفترة الصباحية من الإعدادات
          </div>
        )}
      </SectionCard>

      {/* Evening Schedule */}
      <SectionCard
        title="الفترة المسائية"
        subtitle={`من ${eveningStart} إلى ${eveningEnd} — ${eveningSlots.length} حصة`}
        action={
          <div className="flex items-center gap-1 text-xs">
            <ClockIcon className="size-3.5" />
            <span>
              {eveningStart} - {eveningEnd}
            </span>
          </div>
        }
        contentClassName="px-5 pb-5"
      >
        {eveningSlots.length > 0 ? (
          <ScheduleTable
            title="الفترة المسائية"
            slots={eveningSlots}
            scheduleData={scheduleData}
            onCellChange={handleCellChange}
            periodKey="evening"
          />
        ) : (
          <div className="py-12 text-center text-sm text-app-muted">
            لا توجد حصص — يرجى ضبط أوقات الفترة المسائية من الإعدادات
          </div>
        )}
      </SectionCard>

      {/* Tips */}
      <div className="rounded-xl border border-app-line bg-app-card-soft px-5 py-4 text-xs text-app-muted leading-relaxed space-y-1">
        <p className="font-medium text-app-text text-sm">نصائح الاستخدام</p>
        <p>
          • <strong>انقر</strong> على أي خلية لإضافة أو تعديل اسم المدرب
        </p>
        <p>
          • <strong>Enter</strong> لتأكيد التعديل — <strong>Escape</strong>{" "}
          للإلغاء
        </p>
        <p>
          • <strong>Shift + Enter</strong> لإضافة سطر جديد داخل نفس الخلية
        </p>
        <p>• البيانات تُحفظ تلقائياً ولن تُفقد عند إعادة تحميل الصفحة</p>
        <p>
          • استخدم <strong>إعدادات الأوقات</strong> لتخصيص الفترات ومدة الحصة
        </p>
      </div>
    </div>
  );
}
