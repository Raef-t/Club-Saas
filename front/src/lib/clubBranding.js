export const DEFAULT_BRAND_LOGO_URL = "/img/techno_gym_logo.png";

/**
 * Extracts club records from the response shapes returned by the backend.
 */
export function getBrandClubs(response) {
  if (Array.isArray(response)) return response;
  if (Array.isArray(response?.data)) return response.data;
  if (Array.isArray(response?.data?.data)) return response.data.data;
  return [];
}

/**
 * Selects the club associated with the current branch, with a stable fallback
 * for the all-branches view.
 */
export function selectBrandClub(clubs, selectedClubId, embeddedClub = null) {
  const clubList = Array.isArray(clubs) ? clubs : [];

  if (selectedClubId != null) {
    const matchingClub = clubList.find((club) => String(club.id) === String(selectedClubId));
    if (matchingClub) return matchingClub;

    if (embeddedClub && String(embeddedClub.id) === String(selectedClubId)) {
      return embeddedClub;
    }
  }

  return clubList.find((club) => club.is_active !== false) || clubList[0] || embeddedClub || null;
}

/**
 * Returns the logo field supported by current and older backend responses.
 */
export function getClubLogoValue(club) {
  const value = club?.logo_url || club?.logo;
  if (typeof value === "string") return value.trim();
  if (value && typeof value === "object") {
    return String(value.url || value.path || "").trim();
  }
  return "";
}

/**
 * Routes backend-hosted assets through the same-origin asset endpoint so
 * dynamic logo hosts do not require unsafe wildcard image configuration.
 */
export function resolveClubLogoUrl(club) {
  const value = getClubLogoValue(club);
  if (!value) return DEFAULT_BRAND_LOGO_URL;

  if (value.startsWith("data:") || value.startsWith("blob:")) return value;
  if (value.startsWith("/img/") || value.startsWith("/api/")) return value;

  let assetPath = value;
  try {
    const remoteUrl = new URL(value);
    assetPath = `${remoteUrl.pathname}${remoteUrl.search}`;
  } catch {
    // Relative backend paths are handled below.
  }

  const normalizedPath = assetPath.replace(/^\/+/, "");
  if (!normalizedPath) return DEFAULT_BRAND_LOGO_URL;

  const separator = normalizedPath.includes("?") ? "&" : "?";
  const version = club?.logo_updated_at || club?.updated_at;
  const cacheVersion = version ? `${separator}v=${encodeURIComponent(version)}` : "";

  return `/api/assets/${normalizedPath}${cacheVersion}`;
}

/**
 * Converts a local brand URL to an absolute URL for standalone print windows.
 */
export function getAbsoluteBrandLogoUrl(logoUrl, origin) {
  return new URL(logoUrl || DEFAULT_BRAND_LOGO_URL, origin).href;
}
