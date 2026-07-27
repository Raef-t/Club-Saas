/**
 * Escapes dynamic report values before inserting them into printable HTML.
 */
export function escapeReportHtml(value) {
  return String(value ?? "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}

/**
 * Creates one printable metrics strip.
 */
function buildMetricsHtml(metrics) {
  return metrics
    .map(
      (metric) => `
        <div class="metric">
          <span>${escapeReportHtml(metric.label)}</span>
          <strong>${escapeReportHtml(metric.value)}</strong>
        </div>
      `,
    )
    .join("");
}

/**
 * Creates the printable table for one report.
 */
function buildTableHtml(report) {
  const header = report.columns
    .map((column) => `<th>${escapeReportHtml(column.label)}</th>`)
    .join("");
  const body = report.rows.length
    ? report.rows
        .map(
          (row) => `
            <tr>
              ${report.columns
                .map((column) => `<td>${escapeReportHtml(row[column.key])}</td>`)
                .join("")}
            </tr>
          `,
        )
        .join("")
    : `<tr><td class="empty" colspan="${report.columns.length}">${escapeReportHtml(
        report.emptyMessage,
      )}</td></tr>`;

  return `
    <table>
      <thead><tr>${header}</tr></thead>
      <tbody>${body}</tbody>
    </table>
  `;
}

/**
 * Builds a complete branded print document for one or more reports.
 */
export function buildReportsPrintHtml({ reports, branchName, logoUrl, generatedAt = new Date() }) {
  const generatedLabel = generatedAt.toLocaleString("ar-EG", {
    year: "numeric",
    month: "long",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
  const reportPages = reports
    .map(
      (report) => `
        <section class="report-page">
          <header class="report-header">
            <div class="brand">
              <img src="${escapeReportHtml(logoUrl)}" alt="TechnoGYM" />
              <div>
                <strong>TechnoGYM</strong>
                <span>نظام التقارير التشغيلية</span>
              </div>
            </div>
            <div class="branch">
              <span>الفرع</span>
              <strong>${escapeReportHtml(branchName)}</strong>
            </div>
          </header>

          <div class="report-title">
            <div>
              <h1>${escapeReportHtml(report.title)}</h1>
              <p>${escapeReportHtml(report.description)}</p>
            </div>
            <div class="generated">
              <span>تاريخ الإصدار</span>
              <strong>${escapeReportHtml(generatedLabel)}</strong>
            </div>
          </div>

          <div class="metrics">${buildMetricsHtml(report.metrics)}</div>
          ${buildTableHtml(report)}

          <footer>
            <span>الفرع: ${escapeReportHtml(branchName)}</span>
            <span>عدد السجلات: ${escapeReportHtml(report.rows.length)}</span>
            <span>TechnoGYM</span>
          </footer>
        </section>
      `,
    )
    .join("");

  return `<!doctype html>
    <html lang="ar" dir="rtl">
      <head>
        <meta charset="utf-8" />
        <title>تقارير TechnoGYM - ${escapeReportHtml(branchName)}</title>
        <style>
          @page { size: A4 landscape; margin: 10mm; }
          * { box-sizing: border-box; }
          body {
            margin: 0;
            background: #fff;
            color: #111827;
            font-family: Tahoma, Arial, sans-serif;
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
          }
          .report-page {
            min-height: 185mm;
            display: flex;
            flex-direction: column;
            page-break-after: always;
          }
          .report-page:last-child { page-break-after: auto; }
          .report-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            padding-bottom: 12px;
            border-bottom: 3px solid #fccd03;
          }
          .brand { display: flex; align-items: center; gap: 12px; }
          .brand img {
            width: 118px;
            height: 48px;
            object-fit: contain;
            border-radius: 8px;
            background: #090c11;
          }
          .brand div, .branch, .generated {
            display: flex;
            flex-direction: column;
            gap: 4px;
          }
          .brand strong { font-size: 18px; }
          .brand span, .branch span, .generated span { color: #6b7280; font-size: 11px; }
          .branch {
            min-width: 180px;
            padding: 10px 14px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            text-align: center;
          }
          .branch strong { font-size: 16px; color: #8a6c00; }
          .report-title {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 24px;
            margin: 18px 0 14px;
          }
          h1 { margin: 0; font-size: 22px; }
          p { margin: 6px 0 0; color: #6b7280; font-size: 12px; }
          .generated { min-width: 180px; text-align: left; direction: rtl; }
          .generated strong { font-size: 11px; }
          .metrics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            gap: 10px;
            margin-bottom: 14px;
          }
          .metric {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 9px;
            background: #f9fafb;
          }
          .metric span { color: #4b5563; font-size: 11px; }
          .metric strong { color: #8a6c00; font-size: 16px; }
          table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 10px;
          }
          th {
            padding: 9px 7px;
            background: #111827;
            color: #fff;
            font-weight: 700;
            text-align: right;
          }
          td {
            padding: 8px 7px;
            border: 1px solid #e5e7eb;
            word-break: break-word;
          }
          tbody tr:nth-child(even) { background: #f9fafb; }
          .empty { padding: 28px; color: #6b7280; text-align: center; }
          footer {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            margin-top: auto;
            padding-top: 12px;
            border-top: 1px solid #d1d5db;
            color: #6b7280;
            font-size: 9px;
          }
        </style>
      </head>
      <body>${reportPages}</body>
    </html>`;
}

/**
 * Opens the browser print dialog for the requested reports.
 */
export function printReports(reports, branchName) {
  if (typeof window === "undefined" || !reports.length) return false;

  const printWindow = window.open("", "_blank", "width=1200,height=800");
  if (!printWindow) return false;

  printWindow.opener = null;
  const logoUrl = new URL("/img/test_logo.png", window.location.origin).href;
  const html = buildReportsPrintHtml({
    reports,
    branchName,
    logoUrl,
  });

  printWindow.document.open();
  printWindow.document.write(html);
  printWindow.document.close();
  printWindow.focus();
  printWindow.setTimeout(() => printWindow.print(), 350);
  return true;
}
