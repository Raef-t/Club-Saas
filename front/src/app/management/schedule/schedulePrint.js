import { SCHEDULE_DAYS } from "./scheduleConstants";
import { escapeScheduleHtml } from "./scheduleUtils";
import { DEFAULT_BRAND_LOGO_URL, getAbsoluteBrandLogoUrl } from "@/lib/clubBranding";

/**
 * Preserves user-entered line breaks after escaping printable cell content.
 */
function formatPrintableCell(value) {
  return escapeScheduleHtml(value).replaceAll("\n", "<br />");
}

/**
 * Builds one printable period table.
 */
function buildPeriodTableHtml(title, slots, periodKey, scheduleData, holidayDayKeys, formatTime) {
  if (!slots.length) return "";
  const fmt = typeof formatTime === "function" ? formatTime : (v) => v;
  const holidaySet = new Set(holidayDayKeys);

  const headerCells = slots
    .map(
      (slot) =>
        `<th><div class="time-heading"><div>${escapeScheduleHtml(fmt(slot.from))}</div><div class="time-arrow">↓</div><div>${escapeScheduleHtml(fmt(slot.to))}</div></div></th>`,
    )
    .join("");
  const bodyRows = SCHEDULE_DAYS.map((day, index) => {
    const cells = slots
      .map((slot) => {
        const value = scheduleData?.[day.key]?.[`${periodKey}_${slot.key}`] || "";
        return `<td>${formatPrintableCell(value)}</td>`;
      })
      .join("");
    const isHoliday = holidaySet.has(day.key);
    const rowClasses = [index % 2 ? "even-row" : "", isHoliday ? "holiday-row" : ""]
      .filter(Boolean)
      .join(" ");
    const holidayBadge = isHoliday ? '<span class="holiday-badge">عطلة</span>' : "";

    return `<tr class="${rowClasses}"><td class="day-cell">${escapeScheduleHtml(day.label)}${holidayBadge}</td>${cells}</tr>`;
  }).join("");

  return `
    <section class="period-section">
      <h2>${escapeScheduleHtml(title)}</h2>
      <table>
        <thead><tr><th class="day-cell">اليوم</th>${headerCells}</tr></thead>
        <tbody>${bodyRows}</tbody>
      </table>
    </section>
  `;
}

/**
 * Creates the complete standalone HTML document used for schedule printing.
 */
export function buildSchedulePrintHtml({
  morningSlots,
  eveningSlots,
  scheduleData,
  holidayDayKeys = [],
  formatTime,
  logoUrl = DEFAULT_BRAND_LOGO_URL,
  printedAt = new Date(),
}) {
  const morningHtml = buildPeriodTableHtml(
    "الفترة الصباحية",
    morningSlots,
    "morning",
    scheduleData,
    holidayDayKeys,
    formatTime,
  );
  const eveningHtml = buildPeriodTableHtml(
    "الفترة المسائية",
    eveningSlots,
    "evening",
    scheduleData,
    holidayDayKeys,
    formatTime,
  );
  const printDate = escapeScheduleHtml(printedAt.toLocaleDateString("ar-SY"));
  const printableLogoUrl = escapeScheduleHtml(logoUrl);

  return `<!doctype html>
    <html lang="ar" dir="rtl">
      <head>
        <meta charset="utf-8" />
        <title>جدول الدوام - TechnoGYM</title>
        <style>
          * { box-sizing: border-box; margin: 0; padding: 0; }
          body {
            font-family: Arial, "Tahoma", sans-serif;
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
            border-bottom: 3px solid #f2dc2e;
            padding-bottom: 18px;
            margin-bottom: 10px;
          }
          .header-right {
            display: flex;
            align-items: center;
            gap: 16px;
          }
          .header-right img {
            width: 159px;
            height: auto;
            border-radius: 8px;
          }
          .meta-area {
            color: #555;
            font-size: 12px;
            text-align: left;
          }
          .meta-area p { margin: 3px 0; }
          .main-title {
            margin: 20px 0 8px;
            color: #111;
            font-size: 22px;
            font-weight: 700;
            text-align: center;
          }
          .period-section {
            page-break-after: always;
            page-break-inside: avoid;
          }
          .period-section:last-of-type { page-break-after: auto; }
          .period-section h2 {
            margin: 30px 0 10px;
            color: #111;
            font-size: 18px;
            font-weight: 700;
            text-align: center;
          }
          table {
            width: 100%;
            margin-top: 8px;
            border-collapse: collapse;
          }
          th, td {
            padding: 8px 4px;
            border: 1px solid #d1d5db;
            font-size: 11px;
            text-align: center;
            white-space: pre-wrap;
          }
          th {
            background: #f3f4f6;
            color: #374151;
            font-size: 10px;
            font-weight: 700;
          }
          td {
            color: #1f2937;
            font-weight: 500;
          }
          .time-heading { line-height: 1.4; }
          .time-arrow { color: #999; font-size: 10px; }
          .day-cell {
            min-width: 70px;
            background: #f3f4f6;
            font-size: 13px;
            font-weight: 700;
          }
          .even-row td { background: #fef9e7; }
          .even-row .day-cell { background: #fdf3d3; }
          .holiday-row td {
            background: #fff1f1;
            border-color: #efb3b3;
          }
          .holiday-row .day-cell {
            background: #ffe1e1;
            color: #b91c1c;
          }
          .holiday-badge {
            display: inline-block;
            margin-right: 6px;
            border: 1px solid #efb3b3;
            border-radius: 999px;
            padding: 1px 5px;
            background: #fff5f5;
            color: #b91c1c;
            font-size: 8px;
          }

          .footer {
            margin-top: 40px;
            padding-top: 14px;
            border-top: 1px solid #e2e8f0;
            color: #94a3b8;
            font-size: 10px;
            font-weight: 500;
            text-align: center;
          }
          @media print {
            body { padding: 15px; }
            @page { size: landscape; margin: 10mm; }
            .period-section {
              page-break-after: always;
              page-break-inside: avoid;
            }
            .period-section:last-of-type { page-break-after: auto; }
          }
        </style>
      </head>
      <body>
        <header class="header">
          <div class="header-right">
            <img src="${printableLogoUrl}" alt="TechnoGYM" />
          </div>
          <div class="meta-area">
            <p>تاريخ الطباعة: ${printDate}</p>
            <p>نظام إدارة الصالات الرياضية</p>
          </div>
        </header>
        <h1 class="main-title">جدول الدوام الأسبوعي</h1>
        ${morningHtml}
        ${eveningHtml}
        <footer class="footer">
          نظام تكنولوجي جيم المتكامل لإدارة الأندية الرياضية &copy; جميع الحقوق محفوظة
        </footer>
        <script>
          window.addEventListener("load", function () {
            window.setTimeout(function () {
              window.print();
              window.setTimeout(function () { window.close(); }, 500);
            }, 300);
          });
        </script>
      </body>
    </html>`;
}

/**
 * Opens the generated print document and reports whether the browser allowed it.
 */
export function openSchedulePrintWindow(values) {
  const printWindow = window.open("", "_blank");
  if (!printWindow) return false;

  const logoUrl = getAbsoluteBrandLogoUrl(values.logoUrl, window.location.origin);
  printWindow.document.open();
  printWindow.document.write(buildSchedulePrintHtml({ ...values, logoUrl }));
  printWindow.document.close();
  return true;
}
