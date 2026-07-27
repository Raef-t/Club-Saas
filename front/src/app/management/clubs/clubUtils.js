import { formatLocalizedName } from "../../../lib/utils";

export const EMPTY_CLUB_FORM = {
  name: "",
  logo_url: "",
  is_active: true,
};

/**
 * Extracts a club list from supported backend response shapes.
 */
export function getClubCollection(response) {
  if (Array.isArray(response)) return response;
  if (Array.isArray(response?.data)) return response.data;
  if (Array.isArray(response?.data?.data)) return response.data.data;
  return [];
}

/**
 * Extracts one club from supported backend response shapes.
 */
export function getClubRecord(response) {
  const nestedRecord = response?.data?.data;
  if (nestedRecord && typeof nestedRecord === "object" && !Array.isArray(nestedRecord)) {
    return nestedRecord;
  }

  const record = response?.data;
  if (record && typeof record === "object" && !Array.isArray(record)) {
    return record;
  }

  if (response && typeof response === "object" && !Array.isArray(response)) {
    return response;
  }

  return null;
}

/**
 * Returns the localized display name of a club.
 */
export function getClubName(club) {
  return formatLocalizedName(club?.name);
}

/**
 * Filters clubs by localized name or current status.
 */
export function filterClubs(clubs, search) {
  const normalizedSearch = search.trim().toLowerCase();
  if (!normalizedSearch) return clubs;

  return clubs.filter((club) =>
    [getClubName(club), club.is_active ? "نشط" : "غير نشط"]
      .filter(Boolean)
      .some((value) => String(value).toLowerCase().includes(normalizedSearch)),
  );
}

/**
 * Creates the club statistics displayed above the table.
 */
export function createClubStats(clubs) {
  const activeCount = clubs.filter((club) => club.is_active).length;

  return [
    {
      title: "إجمالي النوادي",
      value: clubs.length.toLocaleString("ar"),
      helper: "كل النوادي المسجلة",
      tone: "yellow",
      compact: true,
    },
    {
      title: "النوادي النشطة",
      value: activeCount.toLocaleString("ar"),
      helper: "النوادي المفتوحة والفعالة",
      tone: "green",
      compact: true,
    },
    {
      title: "النوادي غير النشطة",
      value: (clubs.length - activeCount).toLocaleString("ar"),
      helper: "النوادي المغلقة مؤقتاً",
      tone: "red",
      compact: true,
    },
  ];
}

/**
 * Converts a club record to values understood by the editor form.
 */
export function createClubFormValues(club) {
  if (!club) return { ...EMPTY_CLUB_FORM };

  return {
    name: getClubName(club) === "-" ? "" : getClubName(club),
    logo_url: club.logo_url || "",
    is_active: club.is_active !== false,
  };
}

/**
 * Converts editor values to the backend club contract.
 */
export function createClubPayload(form) {
  return {
    name: form.name.trim(),
    logo_url: form.logo_url.trim() || null,
    is_active: Boolean(form.is_active),
  };
}

/**
 * Checks whether another club already uses the requested name.
 */
export function hasDuplicateClubName(clubs, name, excludedId = null) {
  const normalizedName = name.trim().toLowerCase();

  return clubs.some(
    (club) =>
      String(club.id) !== String(excludedId ?? "") &&
      getClubName(club).trim().toLowerCase() === normalizedName,
  );
}
