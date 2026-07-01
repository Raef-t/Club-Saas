export const CURRENCY_SYMBOL = "ل.س";

export function formatMoney(value, currency = CURRENCY_SYMBOL) {
  const num = Number(value) || 0;
  if (currency === "$") {
    return `$${num.toLocaleString("en-US")}`;
  }
  return `${num.toLocaleString("en-US")} ${currency}`;
}

export function formatDate(value) {
  if (!value) return "-";
  const date = new Date(value);
  if (isNaN(date.getTime())) return "-";

  return new Intl.DateTimeFormat("ar", {
    year: "numeric",
    month: "short",
    day: "numeric",
  }).format(date);
}

export function formatLocalizedName(name) {
  if (!name) return "-";
  if (typeof name === "string") return name;
  return name.ar || name.en || "-";
}
