import { formatLocalizedName } from "../../../lib/utils";

export const EMPTY_BRANCH_FORM = {
  club_id: "",
  name_ar: "",
  gender_restriction: "mixed",
  address: "",
  country_code: "+963",
  phone: "",
  type: "gym",
};

/**
 * Extracts a branch or club list from supported backend response shapes.
 */
export function getBranchCollection(response) {
  if (Array.isArray(response)) return response;
  if (Array.isArray(response?.data)) return response.data;
  if (Array.isArray(response?.data?.data)) return response.data.data;
  return [];
}

/**
 * Extracts one branch from supported backend response shapes.
 */
export function getBranchRecord(response) {
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
 * Returns the localized display name of a branch.
 */
export function getBranchDisplayName(branch) {
  return formatLocalizedName(branch?.name);
}

/**
 * Filters branches by gender, localized name, address, or phone.
 */
export function filterBranches(branches, { search, gender }) {
  const normalizedSearch = search.trim().toLowerCase();

  return branches.filter((branch) => {
    const matchesGender = gender === "all" || branch.gender_restriction === gender;
    const matchesSearch =
      !normalizedSearch ||
      [getBranchDisplayName(branch), branch.address, branch.phone]
        .filter(Boolean)
        .some((value) => String(value).toLowerCase().includes(normalizedSearch));

    return matchesGender && matchesSearch;
  });
}

/**
 * Creates the branch statistics displayed above the table.
 */
export function createBranchStats(branches) {
  const activeCount = branches.filter((branch) => branch.is_active).length;
  const maleCount = branches.filter((branch) => branch.gender_restriction === "male").length;
  const femaleCount = branches.filter((branch) => branch.gender_restriction === "female").length;

  return [
    {
      title: "إجمالي الفروع",
      value: branches.length.toLocaleString("ar"),
      helper: "كل الفروع المسجلة",
      tone: "yellow",
      compact: true,
    },
    {
      title: "الفروع النشطة",
      value: activeCount.toLocaleString("ar"),
      helper: "الفروع التي تعمل حالياً",
      tone: "green",
      compact: true,
    },
    {
      title: "فروع الرجال",
      value: maleCount.toLocaleString("ar"),
      helper: "مخصصة للذكور فقط",
      tone: "blue",
      compact: true,
    },
    {
      title: "فروع السيدات",
      value: femaleCount.toLocaleString("ar"),
      helper: "مخصصة للإناث فقط",
      tone: "purple",
      compact: true,
    },
  ];
}

/**
 * Converts a branch record to values understood by the editor form.
 */
export function createBranchFormValues(branch, defaultClubId = "") {
  if (!branch) {
    return { ...EMPTY_BRANCH_FORM, club_id: String(defaultClubId || "") };
  }

  const nameAr = typeof branch.name === "object" ? branch.name?.ar : branch.name;
  return {
    club_id: String(branch.club_id || branch.club?.id || defaultClubId || ""),
    name_ar: nameAr || "",
    gender_restriction: branch.gender_restriction || "mixed",
    address: branch.address || "",
    country_code: branch.country_code || "+963",
    phone: branch.phone || "",
    type: branch.type || "gym",
  };
}

/**
 * Converts validated editor values to the current backend branch contract.
 */
export function createBranchPayload(form) {
  return {
    club_id: Number(form.club_id),
    name: form.name_ar.trim(),
    gender_restriction: form.gender_restriction,
    type: "gym",
    address: form.address.trim() || null,
    country_code: form.country_code.trim() || null,
    phone: form.phone.trim() || null,
  };
}

/**
 * Creates localized club options for the branch editor.
 */
export function createClubOptions(clubs) {
  return clubs.map((club) => ({
    value: String(club.id),
    label: formatLocalizedName(club.name),
  }));
}
