const CURRENCY_SYMBOL = "$";

/**
 * Converts an API amount into a safe finite number.
 */
export function parseSubscriptionAmount(value) {
  const amount = Number.parseFloat(value || 0);
  return Number.isFinite(amount) ? amount : 0;
}

/**
 * Formats subscription amounts using the currency shown by the dashboard.
 */
export function formatSubscriptionMoney(value) {
  return `${CURRENCY_SYMBOL}${parseSubscriptionAmount(value).toLocaleString("en-US", {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  })}`;
}

/**
 * Extracts the subscription list from the supported backend response shapes.
 */
export function getSubscriptionRows(response) {
  if (Array.isArray(response?.data?.data)) return response.data.data;
  if (Array.isArray(response?.data)) return response.data;
  return [];
}

/**
 * Extracts a single subscription from its backend response.
 */
export function getSubscriptionDetail(response) {
  return response?.data || null;
}
