/**
 * Normalizes API response to array of version items.
 */
export function normalizeVersionList(response) {
  if (!response) return [];
  if (Array.isArray(response)) return response;
  if (Array.isArray(response.data)) return response.data;
  if (response.data && Array.isArray(response.data.data)) return response.data.data;
  return [];
}

/**
 * Filter versions list based on search and filters.
 */
export function filterAppVersions(versions, { search = "", platform = "all", status = "all" }) {
  if (!Array.isArray(versions)) return [];

  const query = search.trim().toLowerCase();

  return versions.filter((item) => {
    // 1. Platform filter
    if (platform !== "all" && item.platform !== platform) {
      return false;
    }

    // 2. Status filter
    if (status === "active" && !item.is_active) {
      return false;
    }
    if (status === "inactive" && item.is_active) {
      return false;
    }

    // 3. Search query filter
    if (query) {
      const matchVersion = item.version_number?.toLowerCase().includes(query);
      const matchBuild = String(item.build_number ?? "").includes(query);
      const matchNotes = item.release_notes?.toLowerCase().includes(query);
      const matchAppName = item.app_name?.toLowerCase().includes(query);

      return matchVersion || matchBuild || matchNotes || matchAppName;
    }

    return true;
  });
}

/**
 * Computes dashboard statistics from versions list.
 */
export function computeAppVersionStats(versions) {
  const list = Array.isArray(versions) ? versions : [];

  const total = list.length;
  const activeCount = list.filter((v) => v.is_active).length;

  const androidActive = list
    .filter((v) => v.platform === "android" && v.is_active)
    .sort((a, b) => (b.build_number || 0) - (a.build_number || 0));

  const iosActive = list
    .filter((v) => v.platform === "ios" && v.is_active)
    .sort((a, b) => (b.build_number || 0) - (a.build_number || 0));

  const latestAndroid = androidActive[0]?.version_number || "لا يوجد";
  const latestAndroidBuild = androidActive[0]?.build_number;

  const latestIos = iosActive[0]?.version_number || "لا يوجد";
  const latestIosBuild = iosActive[0]?.build_number;

  return {
    total,
    activeCount,
    latestAndroid: latestAndroidBuild ? `${latestAndroid} (${latestAndroidBuild})` : latestAndroid,
    latestIos: latestIosBuild ? `${latestIos} (${latestIosBuild})` : latestIos,
  };
}

/**
 * Formats date into Arabic localized string.
 */
export function formatPublishDate(dateString) {
  if (!dateString) return "-";
  try {
    const d = new Date(dateString);
    return new Intl.DateTimeFormat("ar-SY", {
      year: "numeric",
      month: "short",
      day: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    }).format(d);
  } catch {
    return dateString;
  }
}
