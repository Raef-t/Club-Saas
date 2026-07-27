import { GENDER_MAP } from "./settingsConstants";
import { escapeHtml } from "./settingsUtils";

/**
 * Creates the complete printable document for a branch's daily shifts.
 */
export function createShiftPrintDocument({
  branchName,
  shifts,
  formatTime,
  printedAt = new Date(),
}) {
  const rows = shifts
    .map(
      (shift) => `
        <tr>
          <td>${escapeHtml(shift.name || "-")}</td>
          <td>${escapeHtml(formatTime(shift.start_time))}</td>
          <td>${escapeHtml(formatTime(shift.end_time))}</td>
          <td>${escapeHtml(GENDER_MAP[shift.gender_allowed] || shift.gender_allowed || "-")}</td>
        </tr>`,
    )
    .join("");

  return `
    <!doctype html>
    <html lang="ar" dir="rtl">
      <head>
        <meta charset="utf-8" />
        <title>ورديات العمل - TechnoGYM</title>
        <style>
          body {
            font-family: Arial, "Tahoma", sans-serif;
            direction: rtl;
            text-align: right;
            padding: 40px;
            color: #111;
            background: #fff;
          }
          .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #e2b714;
            padding-bottom: 20px;
            margin-bottom: 30px;
          }
          .logo-title {
            margin: 0;
            color: #111;
            font-size: 26px;
            font-weight: 700;
            letter-spacing: 2px;
          }
          .logo-title span {
            color: #e2b714;
          }
          .logo-subtitle {
            margin: 5px 0 0;
            color: #666;
            font-size: 11px;
            font-weight: 500;
          }
          .meta-area {
            color: #555;
            font-size: 13px;
            text-align: left;
          }
          .meta-area p {
            margin: 4px 0;
          }
          h1 {
            margin: 0 0 20px;
            color: #111;
            font-size: 22px;
          }
          .branch-badge {
            display: inline-block;
            margin-bottom: 20px;
            border: 1px solid #f6e398;
            border-radius: 6px;
            padding: 6px 14px;
            background: #fdf5d6;
            color: #927008;
            font-size: 14px;
            font-weight: 700;
          }
          table {
            width: 100%;
            margin-top: 10px;
            border-collapse: collapse;
          }
          th,
          td {
            border: 1px solid #e2e8f0;
            padding: 14px;
            font-size: 14px;
            text-align: center;
          }
          th {
            background: #f8fafc;
            color: #334155;
          }
          td {
            color: #475569;
          }
          tr:nth-child(even) td {
            background: #f8fafc;
          }
          .footer {
            margin-top: 60px;
            border-top: 1px solid #e2e8f0;
            padding-top: 20px;
            color: #94a3b8;
            font-size: 11px;
            text-align: center;
          }
        </style>
      </head>
      <body>
        <header class="header">
          <div>
            <div class="logo-title">TECHNO<span>GYM</span></div>
            <div class="logo-subtitle">نادي تكنولوجي جيم الرياضي</div>
          </div>
          <div class="meta-area">
            <p>تاريخ الطباعة: ${escapeHtml(printedAt.toLocaleDateString("ar-SY"))}</p>
            <p>نظام إدارة الصالات الرياضية</p>
          </div>
        </header>
        <main>
          <h1>جدول ورديات العمل اليومية</h1>
          <div class="branch-badge">الفرع: ${escapeHtml(branchName)}</div>
          <table>
            <thead>
              <tr>
                <th>اسم الوردية</th>
                <th>وقت البدء</th>
                <th>وقت الانتهاء</th>
                <th>الفئة المسموح بها</th>
              </tr>
            </thead>
            <tbody>
              ${rows || '<tr><td colspan="4">لا توجد ورديات عمل مسجلة</td></tr>'}
            </tbody>
          </table>
        </main>
        <footer class="footer">
          نظام تكنولوجي جيم المتكامل لإدارة الأندية الرياضية © جميع الحقوق محفوظة
        </footer>
      </body>
    </html>`;
}

/**
 * Opens a print window and prints the generated shifts document.
 */
export function printBranchShifts(options) {
  const printWindow = window.open("", "_blank");

  if (!printWindow) {
    return false;
  }

  printWindow.opener = null;
  printWindow.document.write(createShiftPrintDocument(options));
  printWindow.document.close();

  printWindow.addEventListener(
    "load",
    () => {
      printWindow.focus();
      printWindow.print();
      printWindow.close();
    },
    { once: true },
  );

  return true;
}
