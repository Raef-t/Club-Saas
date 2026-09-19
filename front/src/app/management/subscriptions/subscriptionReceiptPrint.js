import { formatSubscriptionMoney, getSubscriptionDiscountSummary } from "./subscriptionUtils";

function escapeHtml(value) {
  return String(value ?? "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}

/** Opens a compact printable receipt using the values confirmed by the API. */
export function printSubscriptionReceipt(subscription) {
  const printWindow = window.open("", "_blank", "width=720,height=820");
  if (!printWindow) return false;

  const discount = getSubscriptionDiscountSummary(subscription);
  const memberName = subscription?.member?.person?.full_name || "-";
  const planName =
    typeof subscription?.plan?.name === "string"
      ? subscription.plan.name
      : subscription?.plan?.name?.ar || subscription?.plan?.name?.en || "-";
  const rows = [
    ["السعر الأصلي", formatSubscriptionMoney(discount.originalTotal)],
    ...(discount.isDiscount
      ? [
          [
            `قيمة الحسم (${discount.discountPercentage}%)`,
            `- ${formatSubscriptionMoney(discount.discountAmount)}`,
          ],
        ]
      : []),
    ["الصافي المستحق", formatSubscriptionMoney(discount.finalPrice)],
    ["المبلغ المدفوع", formatSubscriptionMoney(subscription?.paid_amount)],
    ["المتبقي", formatSubscriptionMoney(subscription?.remaining_amount)],
  ];

  printWindow.document.write(`<!doctype html>
    <html lang="ar" dir="rtl">
      <head>
        <meta charset="utf-8" />
        <title>إيصال اشتراك ${escapeHtml(subscription?.subscription_number || subscription?.id)}</title>
        <style>
          body { font-family: Tahoma, Arial, sans-serif; color: #111; margin: 32px; }
          h1 { font-size: 22px; margin: 0 0 20px; }
          .meta { line-height: 1.9; margin-bottom: 22px; }
          table { width: 100%; border-collapse: collapse; }
          td { border-bottom: 1px solid #ddd; padding: 12px 4px; }
          td:last-child { text-align: left; font-weight: 700; }
          .reason { margin-top: 20px; padding: 12px; border: 1px solid #ddd; border-radius: 8px; }
        </style>
      </head>
      <body>
        <h1>وصل قبض اشتراك</h1>
        <div class="meta">
          <div><strong>العضو:</strong> ${escapeHtml(memberName)}</div>
          <div><strong>الخطة:</strong> ${escapeHtml(planName)}</div>
          <div><strong>رقم الاشتراك:</strong> ${escapeHtml(subscription?.subscription_number || subscription?.id || "-")}</div>
        </div>
        <table>${rows
          .map(
            ([label, value]) =>
              `<tr><td>${escapeHtml(label)}</td><td>${escapeHtml(value)}</td></tr>`,
          )
          .join("")}</table>
        ${
          discount.isDiscount && subscription?.discount_reason
            ? `<div class="reason"><strong>سبب الحسم:</strong> ${escapeHtml(subscription.discount_reason)}</div>`
            : ""
        }
      </body>
    </html>`);
  printWindow.document.close();
  printWindow.focus();
  printWindow.setTimeout(() => printWindow.print(), 250);
  return true;
}
