import { useCallback, useState } from "react";

export const DEFAULT_PAGE_SIZE = 15;
export const PAGE_SIZE_OPTIONS = [15, 30, 50, 100];

function toPositiveInteger(value, fallback) {
  const number = Number(value);
  return Number.isInteger(number) && number > 0 ? number : fallback;
}

function toNonNegativeInteger(value, fallback) {
  const number = Number(value);
  return Number.isInteger(number) && number >= 0 ? number : fallback;
}

/**
 * Normalizes the pagination metadata returned by current and legacy list endpoints.
 */
export function getPaginationMeta(response, fallback = {}) {
  const rows = Array.isArray(response?.data)
    ? response.data
    : Array.isArray(response?.data?.data)
      ? response.data.data
      : [];
  const meta = response?.meta || response?.data?.meta || {};
  const fallbackPage = toPositiveInteger(fallback.page, 1);
  const fallbackPerPage = toPositiveInteger(fallback.perPage, DEFAULT_PAGE_SIZE);
  const total = toNonNegativeInteger(meta.total, rows.length);
  const perPage = toPositiveInteger(meta.per_page, fallbackPerPage);
  const lastPage = toPositiveInteger(meta.last_page, Math.max(1, Math.ceil(total / perPage)));
  const currentPage = Math.min(
    toPositiveInteger(meta.current_page, fallbackPage),
    lastPage,
  );

  return {
    currentPage,
    lastPage,
    perPage,
    total,
    from: toNonNegativeInteger(meta.from, total > 0 ? (currentPage - 1) * perPage + 1 : 0),
    to: toNonNegativeInteger(meta.to, Math.min(currentPage * perPage, total)),
  };
}

/**
 * Keeps page state tied to the active server-side filters without an effect-driven reset.
 */
export function useServerPagination(filterKey = "", defaultPerPage = DEFAULT_PAGE_SIZE) {
  const [state, setState] = useState(() => ({
    filterKey,
    page: 1,
    perPage: defaultPerPage,
  }));
  const page = state.filterKey === filterKey ? state.page : 1;
  const perPage = state.perPage;

  const setPage = useCallback(
    (value) => {
      setState((current) => ({
        filterKey,
        page: Math.max(1, Number(value) || 1),
        perPage: current.perPage,
      }));
    },
    [filterKey],
  );

  const setPerPage = useCallback(
    (value) => {
      setState({
        filterKey,
        page: 1,
        perPage: toPositiveInteger(value, defaultPerPage),
      });
    },
    [defaultPerPage, filterKey],
  );

  return { page, perPage, setPage, setPerPage };
}

/** Adds the explicit backend opt-out used by lookup/dropdown requests. */
export function withAllItems(params = {}) {
  return { ...params, per_page: "all" };
}
