export const CURRENCY_SYMBOL = "ل.س";

export function formatMoney(value, currency = CURRENCY_SYMBOL) {
  const num = Number(value) || 0;
  return `${num.toLocaleString("en-US", {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  })} ${currency}`;
}

export function formatDate(value) {
  if (!value) return "-";
  const date = new Date(value);
  if (isNaN(date.getTime())) return "-";

  return new Intl.DateTimeFormat("ar-SY", {
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

export function getBranchesArray(response) {
  if (Array.isArray(response)) return response;
  if (Array.isArray(response?.data)) return response.data;
  if (Array.isArray(response?.data?.data)) return response.data.data;
  return [];
}
