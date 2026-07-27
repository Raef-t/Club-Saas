import { formatLocalizedName } from "../../../lib/utils";

/**
 * Resolves coach branch identifiers into localized display names.
 */
export function getCoachBranchNames(coach, branches = []) {
  const branchIds = Array.isArray(coach?.branch_ids) ? coach.branch_ids : [];

  return (
    branchIds
      .map((id) => {
        const branch = branches.find(
          (candidate) => String(candidate.id) === String(id),
        );
        return branch ? formatLocalizedName(branch.name) : `فرع #${id}`;
      })
      .join("، ") || "-"
  );
}

/**
 * Returns activities that have not yet been assigned to the coach.
 */
export function getUnassignedActivities(coachActivities = [], activities = []) {
  const assignedIds = new Set(coachActivities.map(({ id }) => String(id)));
  return activities.filter(({ id }) => !assignedIds.has(String(id)));
}
