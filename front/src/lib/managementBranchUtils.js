import { formatLocalizedName, getBranchesArray } from "./utils";

export const ALL_BRANCHES_VALUE = "all";
export const MANAGEMENT_BRANCH_COOKIE = "management_branch_id";
export const REPORTS_BRANCH_COOKIE = "reports_branch_id";

/**
 * Returns every branch identifier attached to an entity.
 */
export function getEntityBranchIds(entity) {
  if (!entity || typeof entity !== "object") return [];

  const candidates = [
    entity.branch_id,
    entity.branchId,
    entity.branch?.id,
    entity.member?.branch_id,
    entity.member?.branch?.id,
    entity.player?.branch_id,
    entity.player?.branch?.id,
    entity.player?.member?.branch_id,
    entity.player?.member?.branch?.id,
    entity.plan?.branch_id,
    entity.plan?.branch?.id,
    entity.subscription_plan?.branch_id,
    entity.subscription_plan?.branch?.id,
    ...(Array.isArray(entity.branch_ids) ? entity.branch_ids : []),
    ...(Array.isArray(entity.branches)
      ? entity.branches.map((branch) => (typeof branch === "object" ? branch.id : branch))
      : []),
  ];

  return [
    ...new Set(
      candidates
        .filter((value) => value !== null && value !== undefined && value !== "")
        .map(String),
    ),
  ];
}

/**
 * Filters branch-owned entities using the globally selected branch.
 */
export function filterEntitiesByBranch(items, selectedBranchId, getBranchIds = getEntityBranchIds) {
  if (!Array.isArray(items)) return [];
  if (!selectedBranchId || selectedBranchId === ALL_BRANCHES_VALUE) return items;

  return items.filter((item) => getBranchIds(item).map(String).includes(String(selectedBranchId)));
}

/**
 * Resolves the branch used by a create form without overriding edit values.
 */
export function getPreferredBranchId({
  currentBranchId,
  selectedBranchId,
  branches,
  fallbackToFirst = true,
}) {
  if (currentBranchId !== null && currentBranchId !== undefined && currentBranchId !== "") {
    return String(currentBranchId);
  }

  const branchList = getBranchesArray(branches);
  const selectedExists =
    selectedBranchId &&
    selectedBranchId !== ALL_BRANCHES_VALUE &&
    branchList.some((branch) => String(branch.id) === String(selectedBranchId));

  if (selectedExists) {
    return String(selectedBranchId);
  }

  return fallbackToFirst && branchList[0]?.id != null ? String(branchList[0].id) : "";
}

/**
 * Creates the options rendered by the global management branch selector.
 */
export function createManagementBranchOptions(branches, { includeAll = true } = {}) {
  const branchOptions = getBranchesArray(branches).map((branch) => ({
    value: String(branch.id),
    label: formatLocalizedName(branch.name),
  }));

  return includeAll
    ? [{ value: ALL_BRANCHES_VALUE, label: "كل الفروع" }, ...branchOptions]
    : branchOptions;
}

/**
 * Validates a persisted branch selection against the available branches.
 */
export function normalizeSelectedBranchId(
  selectedBranchId,
  branches,
  { fallbackToFirst = false } = {},
) {
  const branchList = getBranchesArray(branches);
  if (!selectedBranchId || selectedBranchId === ALL_BRANCHES_VALUE) {
    return fallbackToFirst && branchList.length > 0
      ? String(branchList[0].id)
      : ALL_BRANCHES_VALUE;
  }

  const exists = branchList.some((branch) => String(branch.id) === String(selectedBranchId));
  if (exists) return String(selectedBranchId);

  return fallbackToFirst && branchList.length > 0
    ? String(branchList[0].id)
    : ALL_BRANCHES_VALUE;
}

/**
 * Returns the default gender value implied by a branch's gender restriction.
 * When the branch enforces a single gender ("male" or "female"), that gender is
 * returned so form fields can be pre-filled automatically.  Otherwise the
 * caller-provided fallback (default: "male") is returned.
 */
export function getDefaultGenderForBranch(selectedBranch, fallback = "male") {
  const restriction = selectedBranch?.gender_restriction;
  if (restriction === "female" || restriction === "male") {
    return restriction;
  }
  return fallback;
}

/**
 * Resolves the gender imposed by a branch id from a branch collection.
 */
export function getGenderForBranchId(branches, branchId, fallback) {
  const branch = getBranchesArray(branches).find(
    (item) => String(item.id) === String(branchId),
  );

  return getDefaultGenderForBranch(branch, fallback);
}
